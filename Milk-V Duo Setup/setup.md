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

4. Next open up `/duo-buildroot-sdk/build/boards/cv181x/cv1812cp_milkv_duo256m_sd/linux/cvitek_cv1812cp_milkv_duo256m_sd_defconfig` and add these to the last line.
`
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
`
5. Next, go to `duo-buildroot-sdk/device/milkv-duo256m-sd/genimage.cfg` and modify ONLY this part for the size:
`
image rootfs.ext4 {
	ext4 {
		label = "rootfs"
	}
	size = 8G
}
`

6. Now that all the configuration files are set, we can compile the image.
`docker exec -it duodocker /bin/bash -c "cd /home/work && cat /etc/issue && ./build.sh milkv-duo256m-sd"`
The output of the image will be in the `out` folder.

## Copying our compiled kernel into a working ArchLinux with RNDIS already enabled.
We will be downloading the Milk-V Duo 256m ArchLinux Image from here: https://xyzdims.com/3d-printers/misc-hardware-notes/iot-milk-v-duo-risc-v-esbc-running-linux

1. We will replace the kernel with our compiled kernel (ONLY PARTITION 1 replaced)
    - `dd if=duo-buildroot-sdk/out/CompiledArchlinux.img of=archlinux-256m.img bs=512 skip=1 seek=1 count=262144 conv=notrunc`
2. Now, we can flash the downloaded image onto the micro-sd card. (can be through dd/rufus or any tool of your choice.)


## Setting up of RNDIS + HID.