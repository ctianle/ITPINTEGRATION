sudo cp composite_usb /usr/bin/composite_usb
sudo chmod +x /usr/bin/composite_usb

if ! grep -q "/usr/bin/composite_usb" /etc/rc.local; then
    # Insert the line before exit 0
    sudo sed -i '/exit 0/i /usr/bin/composite_usb' /etc/rc.local
fi