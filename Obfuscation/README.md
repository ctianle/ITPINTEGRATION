# Tool: Invoke-Obfuscation

## Proctoring Script
Since the proctoring script has been enhanced and edited, there is a need for additional obfuscation. To obfuscate it, we will be using [https://github.com/danielbohannon/Invoke-Obfuscation](https://github.com/danielbohannon/Invoke-Obfuscation). The following are the steps in obfuscating the script. 

### Setting Up
1. Download the zip file or fork and clone the repository from the provided link onto your desktop.
2. Enter Windows Security on the computer and switch off Real-time protection
3. Using PowerShell, navigate to the directory where you downloaded or cloned "Invoke-Obfuscation." 
```sh
cd Invoke-Obfuscation
```
<a id="step4"></a>

4. Enter Import-Module -Name "path\to\Invoke-Obfuscation\Invoke-Obfuscation.psd1". This will install the module.
```sh
Import-Module -Name "path\to\Invoke-Obfuscation\Invoke-Obfuscation.psd1"
```
5. In the event that there are error messages prompts regarding the files in the Invoke-Obfuscation folder, switch off all anti-viruses temporarily.
6. Key-in "Invoke-Obfuscation" once done. This will start the script obfuscator.
```sh
Invoke-Obfuscation
```

### Obfuscation
8. Enter in the script to obfuscate "set scriptpath C:\path\to\the\script.ps1".
```sh
SET SCRIPTPATH C:\path\to\the\script.ps1
```
9. In the event that it could not set the script path, exit the obfuscator and enter into the directory where the script is found before going back to <a href="#step4">step 4</a>.
10. Key-in TOKEN\STRING\2 to obfuscate string tokens and reorder the commands
```sh
Invoke-Obfuscation>TOKEN\STRING\2
```
11. Type back\back to go back to the home directory.
```sh
Invoke-Obfuscation\TOKEN\STRING>back\back
```
12. Type COMPRESS\1 to convert the entire command to one-liner and compress.
```sh
Invoke-Obfuscation>COMPRESS\1
```
13. Type OUT "path\to\obfuscated_script.ps1". This will output the obfuscated script to the selected directory where the file will be  named obfuscated_script.ps1.
```sh
Invoke-Obfuscation\COMPRESS>OUT "C:\path\to\the\obfuscated_script.ps1"
```
14. For this example, the <a href="https://github.com/ctianle/ITPINTEGRATION/blob/main/Obfuscation/newerCombinedScript.ps1">newerCombinedScript.ps1</a> is used.

As a result, in the event when a student got a hold onto the script, they would not be able to read it and easily decode and translate the script.