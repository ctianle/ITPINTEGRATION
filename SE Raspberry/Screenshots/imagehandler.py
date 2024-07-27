import time
import os
import subprocess
from shutil import move, rmtree
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
import threading
import platform
import resource

def set_affinity(core_id):
    if platform.system() == 'Linux':
        command = f"taskset -cp {core_id} {os.getpid()}"
        subprocess.run(command, shell=True)

class ImageHandler(FileSystemEventHandler):
    def __init__(self, script_to_run, input_folder, processing_folder, completed_folder):
        self.script_to_run = script_to_run
        self.input_folder = input_folder
        self.processing_folder = processing_folder
        self.completed_folder = completed_folder
        self.lock = threading.Lock()

    def on_created(self, event):
        if not event.is_directory and event.src_path.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
            print(f"New image detected: {event.src_path}")
            with self.lock:
                move(event.src_path, self.processing_folder)
            self.run_script()

    def run_script(self):
        start_wall_time = time.time()
        start_cpu_time = resource.getrusage(resource.RUSAGE_SELF)
        
        print(f"Running script: {self.script_to_run}")
        subprocess.run(['python3', self.script_to_run])
        
        end_wall_time = time.time()
        end_cpu_time = resource.getrusage(resource.RUSAGE_SELF)

        wall_time = end_wall_time - start_wall_time
        cpu_time_user = end_cpu_time.ru_utime - start_cpu_time.ru_utime
        cpu_time_system = end_cpu_time.ru_stime - start_cpu_time.ru_stime

        print(f"Processing time for {self.script_to_run}: {wall_time:.2f} seconds")
        print(f"User CPU time for {self.script_to_run}: {cpu_time_user:.2f} seconds")
        print(f"System CPU time for {self.script_to_run}: {cpu_time_system:.2f} seconds")
        
        self.cleanup()

    def cleanup(self):
        with self.lock:
            # Move processed images to the completed folder
            for filename in os.listdir(self.processing_folder):
                file_path = os.path.join(self.processing_folder, filename)
                completed_path = os.path.join(self.completed_folder, filename)
                if os.path.isfile(file_path) or os.path.islink(file_path):
                    move(file_path, completed_path)
                elif os.path.isdir(file_path):
                    move(file_path, completed_path)

                # Remove corresponding folder in roi_results
                roi_folder = os.path.join('./roi_results', os.path.splitext(filename)[0])
                if os.path.isdir(roi_folder):
                    try:
                        rmtree(roi_folder)
                    except Exception as e:
                        print(f'Failed to delete {roi_folder}. Reason: {e}')

    def periodic_cleanup(self):
        while True:
            time.sleep(20)
            self.clear_completed_folder()

    def clear_completed_folder(self):
        with self.lock:
            for filename in os.listdir(self.completed_folder):
                file_path = os.path.join(self.completed_folder, filename)
                try:
                    if os.path.isfile(file_path) or os.path.islink(file_path):
                        os.unlink(file_path)
                    elif os.path.isdir(file_path):
                        rmtree(file_path)
                except Exception as e:
                    print(f'Failed to delete {file_path}. Reason: {e}')

if __name__ == "__main__":
    set_affinity(1)  # Set to core 1

    input_folder = '/home/raspberry/flaskserver/images'
    processing_folder = './processing'
    completed_folder = './completed'
    script_to_run = 'main8.py'

    # Ensure necessary directories exist
    os.makedirs(input_folder, exist_ok=True)
    os.makedirs(processing_folder, exist_ok=True)
    os.makedirs(completed_folder, exist_ok=True)

    event_handler = ImageHandler(script_to_run, input_folder, processing_folder, completed_folder)
    observer = Observer()
    observer.schedule(event_handler, path=input_folder, recursive=False)
    observer.start()

    # Start periodic cleanup in a separate thread
    cleanup_thread = threading.Thread(target=event_handler.periodic_cleanup)
    cleanup_thread.daemon = True
    cleanup_thread.start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
