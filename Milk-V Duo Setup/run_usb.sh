#!/bin/bash

CLASS=acm
VID=0x1d6b
PID=0x0104
MSC_PID=0x1008
RNDIS_PID=0x1009
UVC_PID=0x100A
UAC_PID=0x100B
NCM_PID=0x100C
ADB_VID=0x18D1
ADB_PID=0x4EE0
ADB_PID_M1=0x4EE2
ADB_PID_M2=0x4EE4
MANUFACTURER="Cvitek"
PRODUCT="USB Com Port"
PRODUCT_NCM="NCM"
PRODUCT_RNDIS="RNDIS"
PRODUCT_UVC="UVC"
PRODUCT_UAC="UAC"
PRODUCT_ADB="ADB"
PRODUCT_HID="HID Device"
ADBD_PATH=/usr/bin/
SERIAL="0123456789"
MSC_FILE=$3
CVI_DIR=/tmp/usb
CVI_GADGET=$CVI_DIR/usb_gadget/cvitek
CVI_FUNC=$CVI_GADGET/functions
FUNC_NUM=0
MAX_EP_NUM=16
TMP_NUM=0
INTF_NUM=0
EP_IN=0
EP_OUT=0

case "$2" in
  acm)
    CLASS=acm
    ;;
  msc)
    CLASS=mass_storage
    PID=$MSC_PID
    ;;
  cvg)
    CLASS=cvg
    ;;
  ncm)
    CLASS=ncm
    PID=$NCM_PID
    PRODUCT=$PRODUCT_NCM
    ;;
  rndis)
    CLASS=rndis
    PID=$RNDIS_PID
    PRODUCT=$PRODUCT_RNDIS
    ;;
  hid)
    CLASS=hid
    PRODUCT=$PRODUCT_HID
    ;;
  hid_rndis)
    echo "Setting up HID + RNDIS..."
    CLASS=rndis
    PID=$RNDIS_PID
    PRODUCT=$PRODUCT_RNDIS
    CLASS_HID="hid"
    PRODUCT_HID="HID Device"
    ;;
  *)
    if [ "$1" = "probe" ] ; then
      echo "Usage: $0 probe {acm|msc|cvg|ncm|rndis|uvc|uac1|adb|hid|hid_rndis}"
      exit 1
    fi
esac

calc_func() {
  FUNC_NUM=$(ls $CVI_GADGET/functions -l | grep ^d | wc -l)
  echo "$FUNC_NUM file(s)"
}

create_hid_function() {
  echo "Creating HID function with FUNC_NUM=$FUNC_NUM"
  if [ ! -d $CVI_GADGET/functions ]; then
    echo "ERROR: Directory $CVI_GADGET/functions does not exist"
    return 1
  fi

  mkdir -p $CVI_GADGET/functions/hid.usb$FUNC_NUM
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to create directory $CVI_GADGET/functions/hid.usb$FUNC_NUM"
    return 1
  fi

  echo 1 > $CVI_GADGET/functions/hid.usb$FUNC_NUM/protocol
  echo 1 > $CVI_GADGET/functions/hid.usb$FUNC_NUM/subclass
  echo 8 > $CVI_GADGET/functions/hid.usb$FUNC_NUM/report_length
  echo -ne \\x05\\x01\\x09\\x06\\xA1\\x01\\x05\\x07\\x19\\xE0\\x29\\xE7\\x15\\x00\\x25\\x01\\x75\\x01\\x95\\x08\\x81\\x02\\x95\\x01\\x75\\x08\\x81\\x03\\x95\\x05\\x75\\x01\\x05\\x08\\x19\\x01\\x29\\x05\\x91\\x02\\x95\\x01\\x75\\x03\\x91\\x03\\x95\\x06\\x75\\x08\\x15\\x00\\x25\\x65\\x05\\x07\\x19\\x00\\x29\\x65\\x81\\x00\\xC0 > $CVI_GADGET/functions/hid.usb$FUNC_NUM/report_desc

  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to create HID report descriptor at $CVI_GADGET/functions/hid.usb$FUNC_NUM/report_desc"
    return 1
  fi

  echo "HID function created successfully"
}

probe() {
  echo "Probing USB gadget setup..."
  if [ ! -d $CVI_DIR ]; then
    mkdir $CVI_DIR
  fi
  if [ ! -d $CVI_DIR/usb_gadget ]; then
    # Enable USB ConfigFS
    mount none $CVI_DIR -t configfs
    # Create gadget dev
    mkdir $CVI_GADGET
    # Set the VID and PID
    echo $VID >$CVI_GADGET/idVendor
    echo $PID >$CVI_GADGET/idProduct
    # Set the product information string
    mkdir $CVI_GADGET/strings/0x409
    echo $MANUFACTURER>$CVI_GADGET/strings/0x409/manufacturer
    echo $PRODUCT>$CVI_GADGET/strings/0x409/product
    echo $SERIAL>$CVI_GADGET/strings/0x409/serialnumber
    # Set the USB configuration
    mkdir $CVI_GADGET/configs/c.1
    mkdir $CVI_GADGET/configs/c.1/strings/0x409
    echo "config1">$CVI_GADGET/configs/c.1/strings/0x409/configuration
    # Set the MaxPower of USB descriptor
    echo 120 >$CVI_GADGET/configs/c.1/MaxPower
  fi
  # Get current functions number
  calc_func
  # Assign the class code for composite device
  if [ ! $FUNC_NUM -eq 0 ]; then
    echo 0xEF >$CVI_GADGET/bDeviceClass       # Composite Device
    echo 0x02 >$CVI_GADGET/bDeviceSubClass    # Common subclass for composite
    echo 0x01 >$CVI_GADGET/bDeviceProtocol    # Interface Association Descriptor (IAD)
  fi
  # Create the desired function
  if [ "$2" = "hid" ]; then
    create_hid_function
    ln -s $CVI_FUNC/hid.usb$FUNC_NUM $CVI_GADGET/configs/c.1/hid.usb$FUNC_NUM
  elif [ "$2" = "hid_rndis" ]; then
    # RNDIS Setup
    mkdir -p $CVI_GADGET/functions/rndis.usb$FUNC_NUM
    create_hid_function
    ln -s $CVI_FUNC/rndis.usb$FUNC_NUM $CVI_GADGET/configs/c.1/rndis.usb$FUNC_NUM
    ln -s $CVI_FUNC/hid.usb$FUNC_NUM $CVI_GADGET/configs/c.1/hid.usb$FUNC_NUM
  else
    mkdir $CVI_GADGET/functions/$CLASS.usb$FUNC_NUM
    ln -s $CVI_GADGET/functions/$CLASS.usb$FUNC_NUM $CVI_GADGET/configs/c.1
  fi
}

# Case handling for start/stop/probe commands
case "$1" in
  start)
    UDC=$(ls /sys/class/udc/ | head -n 1)
    echo ${UDC} >$CVI_GADGET/UDC
    ;;
  stop)
    echo "" >$CVI_GADGET/UDC
    find $CVI_GADGET/configs/ -name ".usb" | xargs rm -f
    rmdir $CVI_GADGET/functions/*
    rmdir $CVI_GADGET/strings/0x409/
    rmdir $CVI_GADGET/configs/c.*/
    rmdir $CVI_GADGET
    umount $CVI_DIR
    ;;
  probe)
    probe "$@"
    ;;
  *)
    echo "Usage: $0 probe {acm|msc|cvg|ncm|rndis|uvc|uac1|adb|hid|hid_rndis} {file (msc)}"
    echo "Usage: $0 start"
    echo "Usage: $0 stop"
    exit 1
esac
