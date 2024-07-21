import subprocess

# Load v4l2loopback module with 1 device
modprobe_command = "sudo modprobe v4l2loopback devices=1"
ffmpeg_command = "ffmpeg -f v4l2 -i /dev/video0 -f v4l2 /dev/video2"

# Run modprobe command
modprobe_process = subprocess.Popen(modprobe_command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
modprobe_output, modprobe_error = modprobe_process.communicate()

if modprobe_process.returncode != 0:
    print(f"Error running modprobe command: {modprobe_error.decode('utf-8')}")
    # Handle the error condition if necessary
    
# Optionally, print the output or handle success
print("Command 1 executed successfully.")

# Run ffmpeg command
ffmpeg_process = subprocess.Popen(ffmpeg_command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
ffmpeg_output, ffmpeg_error = ffmpeg_process.communicate()

if ffmpeg_process.returncode != 0:
    print(f"Error running ffmpeg command: {ffmpeg_error.decode('utf-8')}")
    # Handle the error condition if necessary

# Optionally, print the output or handle success
print("Command 2 executed successfully.")
