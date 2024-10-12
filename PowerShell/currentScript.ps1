# Load Windows Forms
Add-Type -AssemblyName System.Windows.Forms

$scriptPath = $MyInvocation.MyCommand.Path
$scriptDirectory = Split-Path -Parent $scriptPath

# Now you can dynamically set paths relative to this script
$configScriptPath = Join-Path -Path $scriptDirectory -ChildPath 'config.ps1'
$functionsScriptPath = Join-Path -Path $scriptDirectory -ChildPath 'functions.ps1'

# Import the scripts
. $configScriptPath
. $functionsScriptPath


# Function to check if a student ID file exists on the server
function Check-StudentID {
    try {
        $response = Invoke-RestMethod -Uri "$base_url/check_studentid" -Method Get
        return $response.exists
    } catch {
        Write-Error "Failed to check student ID on Flask server: $_"
        return $false
    }
}

# Function to prompt for Student ID using a pop-up window
function Prompt-StudentID {
    [void][System.Reflection.Assembly]::LoadWithPartialName("System.Drawing")
    [void][System.Reflection.Assembly]::LoadWithPartialName("System.Windows.Forms")

    $form = New-Object System.Windows.Forms.Form
    $form.Text = "Enter Student ID"
    $form.Size = New-Object System.Drawing.Size(300,150)
    $form.StartPosition = "CenterScreen"

    $label = New-Object System.Windows.Forms.Label
    $label.Text = "Student ID:"
    $label.Location = New-Object System.Drawing.Point(10,20)
    $label.Size = New-Object System.Drawing.Size(80,20)
    $form.Controls.Add($label)

    $textbox = New-Object System.Windows.Forms.TextBox
    $textbox.Location = New-Object System.Drawing.Point(100,20)
    $textbox.Size = New-Object System.Drawing.Size(150,20)
    $form.Controls.Add($textbox)

    $okButton = New-Object System.Windows.Forms.Button
    $okButton.Text = "OK"
    $okButton.Location = New-Object System.Drawing.Point(50,60)
    $okButton.Add_Click({
        $form.Tag = $textbox.Text
        $form.DialogResult = [System.Windows.Forms.DialogResult]::OK
        $form.Close()
    })
    $form.Controls.Add($okButton)

    $cancelButton = New-Object System.Windows.Forms.Button
    $cancelButton.Text = "Cancel"
    $cancelButton.Location = New-Object System.Drawing.Point(150,60)
    $cancelButton.Add_Click({
        $form.Tag = $null
        $form.DialogResult = [System.Windows.Forms.DialogResult]::Cancel
        $form.Close()
    })
    $form.Controls.Add($cancelButton)

    $result = $form.ShowDialog()

    if ($result -eq [System.Windows.Forms.DialogResult]::OK) {
        return $form.Tag
    } else {
        return $null
    }
}

# Check if student ID file exists on the server
$exists = Check-StudentID
if (-not $exists) {
    $studentid = Prompt-StudentID
    if (-not $studentid) {
        Write-Host "No Student ID provided. Exiting script."
        return
    }
} else {
    $studentid = "NotInputted"
}

# Define proctoring functions into a variable to use it in background jobs
$functions = {
    param ($studentid, $scriptPath, $configPath)

    # Dot-source the temporary file to import the functions
    . $scriptPath
    . $configPath

    # Retrieve public key from web server
    try {
        $pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing
        Write-Host "Retrieved public key from web server." -ForegroundColor Green
    } catch {
        Write-Error "Failed to retrieve public key from web server: $_"
        return
    }

    # Send public key to Flask server in JSON format
    $key_data = @{PuK = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($pub_key.tostring()))}
    $completed = $false

    if (Is_Connected -ne $null) {
        try {
            $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($key_data | ConvertTo-Json) -ContentType 'application/json'
            $completed = $true
            Write-Host "Public key sent to Flask server." -ForegroundColor Green
        } catch {
            Write-Error "Failed to send public key to Flask server: $_"
            return
        }

        # Handle the certificates
        Handle_Certificates_Once -studentid $studentid
    } else {
        Write-Error "Raspberry Pi not connected. Exiting script."
        return
    }
}

# Start the certificate handling job
$certJob = Start-Job -ScriptBlock $functions -ArgumentList $studentid, $functionsScriptPath, $configScriptPath

# Wait for the job to complete and output the results
Wait-Job $certJob
Receive-Job $certJob

# Update Token Job
$jobScriptBlock = {
    param ($studentid, $scriptPath, $configPath)

    # Dot-source the temporary file to import the functions
    . $scriptPath
    . $configPath

    while ($true) {
        # Step 1: Check for existing certificate
        $cert_response = Check_Cert
        if ($cert_response.status -eq "cert_exists") {
            Write-Host "Existing certificate found. Verifying..."

            # Step 2: Verify the existing certificate
            $verify_response = Verify_Cert -cert_response $cert_response -c2_verify_url $c2_verify_url
            if ($verify_response.status -eq "success") {
                Write-Host "Certificate is valid." -ForegroundColor Green

                # Step 3: Redirect the token to the Flask server
                $redirect_response = Send_Token_To_Flask -combined_encrypted_data $verify_response.message -base_url $base_url
                if ($redirect_response -eq $null) {
                    Write-Error "Failed to redirect response to Flask server."
                } else {
                    Write-Host "Token successfully sent to Flask server." -ForegroundColor Green
                }
            } else {
                Write-Error "Verification of certificate failed: $($verify_response.error)"
            }
        } else {
            Write-Host "No valid certificate found. Exiting job."
        }

        Start-Sleep -Seconds 1800
    }
}

# Update Token Job
$jobParams = @{
    ScriptBlock = $jobScriptBlock
    ArgumentList = @($studentid, $functionsScriptPath, $configScriptPath)
}

$RefreshToken = Start-Job @jobParams

# Function: Determine if Raspberry Pi is connected to the PC
# Finding for USB with VID 1D6B. VID is the Vendor ID set by us when configuring composite mode on Raspberry Pi
function Is_Connected {
    $check = $null
    $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }

    if ($null -ne $check) {
        return 'FOUND'
    }
}

if (Is_Connected -ne $null) {
    function Encode($data){
        return [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($data))
    }
    $pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing
    $key_data = @{PuK = Encode($($pub_key.tostring()))}
    $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($key_data|ConvertTo-Json) -ContentType 'application/json'
    $uuid = ($response.content | ConvertFrom-Json).uuid
    Write-Host $uuid

    # Load Windows Forms
    Add-Type -AssemblyName System.Windows.Forms

    # ======================================================================================================================================
                                                            # VM DETECTION STARTS HERE
    # ======================================================================================================================================

    # ================================== Detect VM based on BIOS information ==========================================
    $BiosInfo = Get-WmiObject -Class Win32_BIOS | Format-List *

    # Convert $BiosInfo to string to make it searchable
    $BiosInfoString = $BiosInfo | Out-String

    # Define an array of keywords
    $VMkeywords = @("VBOX ", "VirtualBox", "VMware", "VMW")

    # Initialize a flag to track if any keyword is found
    $VMfound = $false

    # Check if any of the keywords are present in $BiosInfoString
    foreach ($VMkeyword in $VMkeywords) {
        if ($BiosInfoString -match $VMkeyword) {
            $VMfound = $true
            break
        }
    }

    # ==================================== Detect VM based on Temperature ==========================================

    # Code for detecting if temperature can be detected
    try {
        $Temperature = Get-WmiObject MSAcpi_ThermalZoneTemperature -Namespace "root/wmi" -ErrorAction Stop
        # Handle the successful case if needed
    } catch {
        if ($_.Exception.Message -match "Not supported") {
            # Prevent message box pop up to account for some systems not supporting this WmiObject
            # $messageBox = [System.Windows.Forms.MessageBox]::Show("(Temperature)Virtual machine software detected. Rapid can't be used in virtual machine software.", "RAPID", [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Information)
            # ============ TO BE DELETED BEFORE RELEASING =================
            # ============ SEND DATA TO WEB SERVER ============================
            $VM += "VM possibly detected, temperature not supported on the PC"
            # Write-Output "An unexpected error occurred: $_"
            # break
        }else{
        }
    }

    # ================================== Detect VM based on SMART DATA information ==========================================
    try {
        $SMART =  Get-WmiObject -Namespace "root\wmi" -Class MSStorageDriver_FailurePredictStatus -ErrorAction Stop
        # Handle the successful case if needed
    } catch {
        if ($_.Exception.Message -match "Not supported") {
            $messageBox = [System.Windows.Forms.MessageBox]::Show("(SMARTDATA)Virtual machine software detected. Rapid can't be used in virtual machine software.", "RAPID", [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Information)
            # ============ TO BE DELETED BEFORE RELEASING =================
            # ============ SEND DATA TO WEB SERVER ============================
            $VM += "VM detected, no SMART DATA info found"
            $VMfound = $true
            Write-Output "An unexpected error occurred: $_"
            # break
        }else{
        }
    }

    # ==================================== Detect VM based on Memory speed information ==========================================
    $MemoryInfo = Get-WmiObject -Class Win32_PhysicalMemory | select speed

    # Display the memory speed information
    if ($MemoryInfo -and $MemoryInfo.Speed -ne $null -and $MemoryInfo.Speed -ne '') {
        Write-Output "Memory speed information:"
        $MemoryInfo | Format-Table -AutoSize
    } else {
        # ============ SEND DATA TO WEB SERVER ============================
        $messageBox = [System.Windows.Forms.MessageBox]::Show("(MEMORY SPEED)Virtual machine software detected. Rapid can't be used in virtual machine software.", "RAPID", [System.Windows.Forms.MessageBoxButtons]::OK, [System.Windows.Forms.MessageBoxIcon]::Information)
        Write-Output "No valid memory speed information found."
        $VMfound = $true
        $VM += "VM detected, no memory speed info found"
        # break
    }

    if($VMfound -eq $true){
        Write-Host $VM
        # Send the captured data to the server
        try {
            $body = @{VM = Encode($VM)}
            $response = Invoke-WebRequest -Uri $base_url -Method Post -Body ($body | ConvertTo-Json) -ContentType "application/json"
            Invoke-WebRequest -Uri $sending_base -UseBasicParsing -Method POST -Body ($response.content|ConvertTo-Json) -ContentType 'application/json'
            Write-Host "Data sent to C2 server successfully."
            break
        } catch {
            Write-Error "Failed to send VM detection data to C2 server: $_"
                return
            }
        } else {
            Write-Host "NO VM DETECTED"
        }
} else {break}

# Function to get the encrypted Fernet key from the Flask server
function Get-EncryptedFernetKey {
    try {
        $response = Invoke-RestMethod -Uri "$base_url/get_encrypted_key" -Method Get
        return $response.encrypted_key
    } catch {
        Write-Error "Failed to get encrypted Fernet key from Flask server: $_"
        return $null
    }
}

# Get the encrypted Fernet key from the RPi (Flask server)
$encrypted_fernet_key = Get-EncryptedFernetKey
if (-not $encrypted_fernet_key) {
    Write-Host "No Fernet key received. Exiting script."
    return
}

# Send the encrypted Fernet key to the C2 server
try {
    $body = @{encrypted_key = $encrypted_fernet_key}
    $encrypted_script = Invoke-WebRequest -Uri $fernet_key_endpoint -Method Post -Body ($body | ConvertTo-Json) -ContentType "application/json"
    $encrypted_functions_script = ($encrypted_script.Content | ConvertFrom-Json).encrypted_functions_script
    Write-Host "Fernet key sent to C2 server successfully."
} catch {
    Write-Error "Failed to send Fernet key to C2 server: $_"
    return
}

# Load necessary assemblies for cryptographic operations
Add-Type -AssemblyName System.Security

# Generate RSA-4096 key pair
$rsa = New-Object System.Security.Cryptography.RSACryptoServiceProvider(4096)

# Export the public key
$publicKey = $rsa.ToXmlString($false)
$publicKeyBase64 = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($publicKey))

# Step 3: Send Encrypted Functions Script to Flask Server
$functions_script_data = @{encrypted_script = $encrypted_functions_script; public_key = $publicKeyBase64}
$response = Invoke-WebRequest -Uri $receive_script_and_key -Method POST -Body ($functions_script_data | ConvertTo-Json) -ContentType 'application/json'
Write-Host "Public key and encrypted script sent to Flask server successfully."
$response_content = $response.Content | ConvertFrom-Json
# Extract re-encrypted script from response
$re_encrypted_script = $response_content.re_encrypted_script

# Base64 decode the re-encrypted script
$re_encrypted_script_bytes = [Convert]::FromBase64String($re_encrypted_script)

# Function to decrypt data in chunks
function Decrypt-InChunks {
    param (
        [byte[]]$data,
        [System.Security.Cryptography.RSACryptoServiceProvider]$rsa
    )

    $chunkSize = $rsa.KeySize / 8
    $decryptedChunks = @()

    for ($i = 0; $i -lt $data.Length; $i += $chunkSize) {
        $chunk = $data[$i..($i + $chunkSize - 1)]
        $decryptedChunk = $rsa.Decrypt($chunk, $false)  # $false for PKCS1.5 padding
        $decryptedChunks += $decryptedChunk
    }

    return [byte[]]$decryptedChunks
}

# Decrypt the script using the private RSA key
try {
    $decryptedBytes = Decrypt-InChunks -data $re_encrypted_script_bytes -rsa $rsa
    $decryptedScript = [System.Text.Encoding]::UTF8.GetString($decryptedBytes)
    Invoke-Expression $decryptedScript
} catch {
    Write-Error "Decryption failed: $_"
}

# Wait for all jobs to complete
Wait-Job $heartbeat
Wait-Job $job1
Wait-Job $job2
Wait-Job $job3
Wait-Job $job4
Wait-Job $RefreshToken

# Retrieve and display outputs from all jobs
$heartbeatOutput = Receive-Job -Job $heartbeat -Keep
$job1Output = Receive-Job -Job $job1 -Keep
$job2Output = Receive-Job -Job $job2 -Keep
$job3Output = Receive-Job -Job $job3 -Keep
$job4Output = Receive-Job -Job $job4 -Keep
$jobOutput = Receive-Job -Job $RefreshToken -Keep

Write-Host "Heartbeat Output: $heartbeatOutput"
Write-Host "Active Win Output: $job1Output"
Write-Host "Display Prop Output: $job2Output"
Write-Host "Proc List Output: $job3Output"
Write-Host "Open Win Output: $job4Output"
Write-Host "Refresh Token Output: $jobOutput"