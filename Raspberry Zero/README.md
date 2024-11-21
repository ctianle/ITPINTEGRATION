# Raspberry Pi Additional Setup
> `check_service_ready.sh` Script for Monitoring Service Startup <br>
> `RPI_Keyboard.py` HID Keyboard script to type keystrokes on user PC

## Testing Functionality
Monitor the script in real-time using logs:
```bash
tail -f service_check.log
```
This will display the current progress and status of the monitored services.

## Script Setup Guide
Ideally it should be at the /home/raspberry directory, else you can edit the links inside the bash script as well.
1. **Editing `check_service_ready.sh`**  
   Add new services to monitor as needed by editing the `SERVICES` array in the script:
   ```bash
   SERVICES=(
       "ExistingService1:LogKeyword1"
       "ExistingService2:LogKeyword2"
       "NewServiceName:NewLogKeyword"
   )
   ```
   Replace `NewServiceName` with the name of the service and `NewLogKeyword` with the corresponding keyword to detect in the service logs.

2. **Cronjob Setup for Automatic Execution on Reboot**  
   Schedule the script to run automatically after every reboot by adding it to the crontab:
   ```bash
   crontab -e
   ```
   Add the following line to the crontab:
   ```bash
   @reboot /bin/bash /path/to/check_service_ready.sh
   ```
   Save and exit the editor. This ensures the script starts monitoring services every time the system boots.