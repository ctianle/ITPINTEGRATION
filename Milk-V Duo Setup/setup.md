# Milk-V Duo 256MB RNDIS+HID Setup

The Milk-V Duo consist of 3 partitions.
1st Partition: **Boot partition**: This holds the bootloader and kernel images.
2nd Partition: **Root Filesystem**: This holds the operating system and user data
3rd Partition: **Swap Partition**: Virtual Memory

## Compiling of Kernel to enable the required drivers that allows us for RNDIS and HID.
Docker will be used to compile the kernel. Installation of docker is required. Additionally, please refer to https://github.com/milkv-duo/duo-buildroot-sdk should you require setting it up using an Ubuntu VM.

1. `git clone https://github.com/milkv-duo/duo-buildroot-sdk.git --depth=1`
2. `cd duo-buildroot-sdk`
3. `docker run -itd --name duodocker -v $(pwd):/home/work milkvtech/milkv-duo:latest /bin/bash`

Alternatively for ARM users, do a `docker run -itd --platform linux/amd64 --name duodocker -v $(pwd):/home/work milkvtech/milkv-duo:latest /bin/bash`

4. Next open up
`/duo-buildroot-sdk/build/boards/cv181x/cv1812cp_milkv_duo256m_sd/linux/cvitek_cv1812cp_milkv_duo256m_sd_defconfig` and add these to the last line.

CONFIG_ADVISE_SYSCALLS=n
CONFIG_CGROUPS=y
CONFIG_CGROUP_FREEZER=y
CONFIG_CGROUP_PIDS=y
CONFIG_CGROUP_DEVICE=y
CONFIG_CPUSETS=y
CONFIG_PROC_PID_CPUSET=y
CONFIG_CGROUP_CPUACCT=y
CONFIG_PAGE_COUNTER=y
CONFIG_MEMCG=y
CONFIG_CGROUP_SCHED=y
CONFIG_NAMESPACES=y
CONFIG_OVERLAY_FS=y
CONFIG_AUTOFS4_FS=y
CONFIG_SIGNALFD=y
CONFIG_TIMERFD=y
CONFIG_EPOLL=y
CONFIG_IPV6=y
CONFIG_FANOTIFY # NOT A TYPO
CONFIG_USB_G_HID=y
CONFIG_USB_CONFIGFS_F_HID=y
CONFIG_USB_G_MULTI=y

6. Next, go to
`duo-buildroot-sdk/device/milkv-duo256m-sd/genimage.cfg` and modify ONLY this part for the size:

image rootfs.ext4 {
	ext4 {
		label = "rootfs"
	}
	size = 8G
}


7. Now that all the configuration files are set, we can compile the image.
`docker exec -it duodocker /bin/bash -c "cd /home/work && cat /etc/issue && ./build.sh milkv-duo256m-sd"`
The output of the image will be in the `out` folder.

## Copying our compiled kernel into a working ArchLinux with RNDIS already enabled.
We will be downloading the Milk-V Duo 256m ArchLinux Image from here: https://xyzdims.com/3d-printers/misc-hardware-notes/iot-milk-v-duo-risc-v-esbc-running-linux

1. We will replace the kernel with our compiled kernel (ONLY PARTITION 1 replaced)
    - `dd if=duo-buildroot-sdk/out/CompiledArchlinux.img of=archlinux-256m.img bs=512 skip=1 seek=1 count=262144 conv=notrunc`
2. Now, we can flash the downloaded image onto the micro-sd card. (can be through dd/rufus or any tool of your choice.)


## Setting up of RNDIS + HID.
When the Milk-V Duo is plugged in, the rndis will auto start-up.
Ssh into the Milk-V Duo using `ssh root@192.168.42.1`
Password: `milkv`

### Configuring internet access
To continue we will need to have internet access to install packages such as nano. Depending on your OS, pick accordingly.

#### Linux 
##### On the Host PC (Ubuntu)
Run the following commands
1. `sysctl net.ipv4.ip_forward=1`

2. `iptables -P FORWARD ACCEPT`

3. `iptables -t nat -A POSTROUTING -s 192.168.42.0/24 -o <YOUR UBUNTU INTERFACE FACING INTERNET> -j MASQUERADE`

##### On the Milk-V Duo
Run the following commands
1. `ip r add default via ip_of_host`/

2. `echo "nameserver 8.8.8.8" >> /etc/resolv.conf`

#### Windows (Internet sharing)
Ensure that you are already connected to the internet.

##### On the host
1. In administrator mode on the powershell ensure `netsh interface ipv4 set interface <milk-v network> forwarding=enabled` is used to enable forwarding

2. `route add 0.0.0.0 mask 0.0.0.0 192.168.137.1`

##### On the milk-v Duo
3. Delete the defaulted 42.2 route on the milk v duo, using `ip route del default via 192.168.42.2 dev usb0`

4. Ensure default route is set on milk v duo example, `ip addr add 192.168.137.2/24 dev usb0` and `ip r add default via 192.168.137.1 dev usb0`

5. Run `ip r` to view all default routes

6. Enter `echo "nameserver 8.8.8.8" > /etc/resolv.conf`

7. Ensure that the host is connected to internet and the wifi sharing is allowed on the same connection as the milk v duo

8. The connection will be cut off, so ssh in using the new ip provided for it instead of the old one, for example, 192.168.137.1 instead of 192.168.42.1”

### Installation of Nano
1. Enable the Milk-V duo to update its time automatically. `sudo timedatectl set-ntp true`

2. Once inside, run `pacman -Sy` or `pacman -Syy` to sync the database

3. Run `pacman -S nano` to install nano package

### Run_usb.sh
1. Insert the codes of run_usb.sh into the milk-v at the path /etc/run_usb.sh.

2. Do a `chmod +x run_usb.sh` to convert it to a executable.

### Running the run_usb.sh script on startup
#### Systemd
1. Insert the run_usb.service into the system folder. `nano /etc/systemd/system/run_usb.service`

#### udev rules
1. Insert the 99-usb-gadget.rules into the path `nano /etc/udev/rules.d/99-usb-gadget.rules`

2. Fill in the vendor id and product id accordingly to when the device is connected. 

#### Reloading and starting the rules 
1. Run the following commands to reload the udev rules and reload the service. The udev rules will run the systemd services.
`udevadm control --reload-rules`
`udevadm trigger`
`systemctl daemon-reload`
`systemctl enable run_usb.service`
A symlink will be created.

2. Run `systemctl start run_usb.service`. This starts the service.

3. Run `systemctl status run_usb.service` to ensure the service is a success.

## Testing
### HID
1. To check if the hid is successful, check that in the milk-v duo, the path /dev/hidg0 was created automatically.

2. In the device manager, it shows HID Keyboard Device

3. Insert the code from https://randomnerdtutorials.com/raspberry-pi-zero-usb-keyboard-hid/, save it as a pythonhid.py file and run it in the Milk-V Duo.

### RNDIS
1. In the device manager, it should be there under network adapters.

2. Conduct a ping test.
