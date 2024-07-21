import subprocess
import time
import threading
import os

# Paths to the scripts you want to run
scripts = [
    "read_camera.py",
    "captures.py",
    "face_detect_eyes.py",
    "face_recog.py"
]

# Delay times in seconds between each script
delays = [12, 12, 200, 200]

# Keep track of processes
processes = []

def run_script(script):
    # Start the script in the background
    process = subprocess.Popen(
        ["python", script],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True
    )
    return process

def read_output(process):
    for line in process.stdout:
        print(line, end='')

def read_error(process):
    for line in process.stderr:
        print(line, end='')

# Start all scripts and their output threads
for i, script in enumerate(scripts):
    process = run_script(script)
    processes.append(process)
    
    # Create threads to read stdout and stderr
    threading.Thread(target=read_output, args=(process,)).start()
    threading.Thread(target=read_error, args=(process,)).start()

    print(f"Started script {script} with PID {process.pid}")

    # Wait for the specified delay before starting the next script
    if i < len(delays):
        time.sleep(delays[i])

# Confirm all scripts have started
all_started = all(process.poll() is None for process in processes)
if all_started:
    print("All scripts have been started successfully.")
else:
    print("Some scripts failed to start.")

# Optionally, terminate processes after some time or based on a condition
time.sleep(60)  # Let the scripts run for 60 seconds

for process in processes:
    process.terminate()
    process.wait()
    print(f"Process {process.pid} terminated with exit code {process.returncode}")

