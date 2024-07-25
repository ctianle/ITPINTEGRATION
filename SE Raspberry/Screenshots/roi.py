import cv2
import os
import time
import psutil

def generate_roi(file_path, output_base_dir):
    print(file_path)
    print(f"Generating ROI for {file_path}...")

    # Start measuring time and CPU usage
    start_time = time.time()
    cpu_times_start = [psutil.cpu_times(percpu=True)[i].user for i in range(psutil.cpu_count())]

    # Load the image in color
    image = cv2.imread(file_path)
    if image is None:
        print(f"Error loading image: {file_path}")
        return None, 0

    # Extract the filename without extension and create output path
    file_name = os.path.splitext(os.path.basename(file_path))[0]
    output_dir = os.path.join(output_base_dir, file_name)

    try:
        os.makedirs(output_dir, exist_ok=True)
    except OSError:
        print(f"Creation of the directory {output_dir} failed")
        return None, 0
    else:
        print(f"Successfully created the directory {output_dir}")

    # Resize the image
    height, width, _ = image.shape
    resize_ratio = 1
    resized_image = cv2.resize(image, (int(width * resize_ratio), int(height * resize_ratio)))

    # Convert to grayscale for edge detection and thresholding
    gray = cv2.cvtColor(resized_image, cv2.COLOR_BGR2GRAY)

    # Apply Gaussian blur
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)

    # Apply Canny Edge detection
    edges = cv2.Canny(blurred, 100, 200)

    # Dilate the edges image to close gaps between lines of text
    dilated_edges = cv2.dilate(edges, None, iterations=9)

    # Find contours
    contours, _ = cv2.findContours(dilated_edges.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    counter = 0
    for contour in contours:
        # Get bounding box
        x, y, w, h = cv2.boundingRect(contour)

        if h < w - 35 or h > w + 35:
            # Extract region of interest from the color image
            roi = resized_image[y:y + h, x:x + w]
            roi_path = os.path.join(output_dir, f'roi_{file_name}_{counter}.png')
            cv2.imwrite(roi_path, roi)
            counter += 1

    # End time and CPU usage measurement
    end_time = time.time()
    cpu_times_end = [psutil.cpu_times(percpu=True)[i].user for i in range(psutil.cpu_count())]

    # Calculate the difference in CPU times
    cpu_times_used = [end - start for start, end in zip(cpu_times_start, cpu_times_end)]

    # Calculate the average CPU time used
    average_time = sum(cpu_times_used) / len(cpu_times_used)

    print(f"Average CPU Time used per core: {average_time:.6f} seconds")
    total_time = end_time - start_time

    print(f"Completing ROI for {file_name}")
    print(f"Total time taken: {total_time:.2f} seconds")

    return file_name, counter
