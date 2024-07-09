from flask import Flask
import subprocess

app = Flask(__name__)

@app.route('/execute_powershell_script', methods=['GET'])
def execute_powershell_script():
    powershell_script_path = 'kill_processes.ps1'
    try:
        result = subprocess.run(['powershell.exe', '-ExecutionPolicy', 'Bypass', '-File', powershell_script_path], capture_output=True, text=True)
        return result.stdout + result.stderr
    except Exception as e:
        return str(e)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=80)
