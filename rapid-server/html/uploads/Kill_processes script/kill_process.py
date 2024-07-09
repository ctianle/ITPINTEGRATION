#PUT THIS FILE IN THE RASPBERRY PI
import requests
import time

url = 'http://192.168.45.222:80/execute_powershell_script'

while True:
    try:
        response = requests.get(url)
        print(response.text)
    except requests.exceptions.RequestException as e:
        print('Failed to send request:', e)
        # Handle the failure, e.g., retrying after a delay
        time.sleep(5)
    time.sleep(60)  # Check for script updates every minute