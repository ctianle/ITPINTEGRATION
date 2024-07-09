# Load Windows Forms
Add-Type -AssemblyName System.Windows.Forms

# Base URL of the Flask server
$base_url = "http://10.0.0.1:5000"
$c2_root_ca_url = "http://192.168.1.122:8080/get_root_ca.php"


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
    param ($studentid)

    # Dot-source the temporary file to import the functions
    . C:\Users\chuat\Desktop\PowershellScripts\functions.ps1
    
    # Define necessary variables
    $key = 'http://192.168.1.122:8080/get_public_key.php'
    $base_url = 'http://10.0.0.1:5000'
    $c2_signing_url = 'http://192.168.1.122:8080/sign_csr.php'
    $c2_verify_url = 'http://192.168.1.122:8080/verify_cert.php'
    $c2_root_ca_url = 'http://192.168.1.122:8080/get_root_ca.php'

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
$certJob = Start-Job -ScriptBlock $functions -ArgumentList $studentid

# Wait for the job to complete and output the results
Wait-Job $certJob
Receive-Job $certJob


# Define proctoring functions into a variable to use it in background jobs
$functions = {
    #---------------------------------------------------------------------------------------------------------------------
    #                                                   Variables Definition
    #---------------------------------------------------------------------------------------------------------------------
    #Web Server for interval tracking
    $interval_base = 'http://192.168.1.122:8080/interval.php?'
    #Web server for processing string data
    $sending_base = 'http://192.168.1.122:8080/process.php'
    #Web server for processing list data
    $sending_list_base = 'http://192.168.1.122:8080/process_list.php'
    #Webserver for getting public key
    $key = 'http://192.168.1.122:8080/get_public_key.php'
    #Heartbeat
    $heartbeat = 'http://192.168.1.122:8080/ping.php?'
    #Proctoring Device's URL
    $base_url = 'http://10.0.0.1:5000'


    #---------------------------------------------------------------------------------------------------------------------
    #                                                      Basic Functions
    #---------------------------------------------------------------------------------------------------------------------
    # Function: Determine if Raspberry Pi is connected to the PC
    # Finding for USB with VID 1D6B. VID is the Vendor ID set by us when configuring composite mode on Raspberry Pi
    function Is_Connected{
        $check = $null
        $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }

        if( $null -ne $check){
            return 'FOUND'
        }
    }

    #Function: Encoding plaintext string to base64 encode so that the proctoring results is not so obvious when sending to flaskserver
    function Encode($data){
        return [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($data))
    }

    #---------------------------------------------------------------------------------------------------------------------
    #                                                    Initial Functions
    #---------------------------------------------------------------------------------------------------------------------
    #Retrieving publick key from web server
    $pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing

    # Sending public key to Pi's Flask server in JSON format            
    $key_data = @{PuK = Encode($($pub_key.tostring()))}
    # Result is the UUID 
    $completed = $false

    if (Is_Connected -ne $null){
        try{
            $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($key_data|ConvertTo-Json) -ContentType 'application/json'
            $uuid = ($response.content | ConvertFrom-Json).uuid
            $completed = $true
        }
        catch{}
    }
    else{break}
    
    #---------------------------------------------------------------------------------------------------------------------
    #                                                     Heartbeat Functions
    #---------------------------------------------------------------------------------------------------------------------
    #Sending heartbeat to webserver to signify that 'I am still connected'
    #It will be using the unique MAC address to differentiate the different devices

    function Send_HeartBeat{
        while(1){
            if (Is_Connected -ne $null){

                $completed = $false
                #Sending JSON data to Flask server: By taking advantage of the POST request
                $data = @{Token = Encode("Token")}
                
                while (-not $completed){
                    try{
                        $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($data|ConvertTo-Json) -ContentType 'application/json'
                        #Sending data to Webserver 
                        Invoke-WebRequest -Uri $heartbeat -UseBasicParsing -Method POST -Body ($response.content|ConvertTo-Json) -ContentType 'application/json'
                        $completed = $true
                    }catch{}
                }
                Start-Sleep -s 5
            }
            else{break}
        }
    }

    #---------------------------------------------------------------------------------------------------------------------
    #                                                      Proctoring Functions
    #---------------------------------------------------------------------------------------------------------------------
    # Function: Display process name and the mainwindowtitle of the current active Windows.
    # Add-Type (cmdlet): Allows the definition of a Microsoft .NET Core class in Powershell session, we can then instantiate objects, by using the New-Object cmdlet and use the objects
    # Public class APIFuncs: contains three static methods that utilizes the user32.dll functions GetWindowText, GetForegroundWindow and GetWindowTextLength
    function Get_Active_Win{
        Add-Type  @'
        using System;
        using System.Runtime.InteropServices;
        using System.Text;
        
        public class APIFuncs
        {
            [DllImport("user32.dll", CharSet = CharSet.Auto, SetLastError = true)]
                public static extern int GetWindowText(IntPtr hwnd,StringBuilder lpString, int cch);
        
            [DllImport("user32.dll", SetLastError=true, CharSet=CharSet.Auto)]
                public static extern IntPtr GetForegroundWindow();
        
            [DllImport("user32.dll", SetLastError=true, CharSet=CharSet.Auto)]
                public static extern Int32 GetWindowTextLength(IntPtr hWnd);
            }
'@
            while(1){
                # After calling Add-Type, we can freely use the called Windows API 
                # The two colon '::' idicates that we are calling a static .NET method
                if (Is_Connected -ne $null){
                    $w = [APIFuncs]::GetForegroundWindow() 
                    $len = [APIFuncs]::GetWindowTextLength($w) 
                    $sb = New-Object text.stringbuilder -ArgumentList ($len + 1)
                    $rtnlen = [APIFuncs]::GetWindowText($w,$sb,$sb.Capacity)
                    
                    if ([string]::IsNullOrEmpty($sb.ToString())){$sb = 'No Active Window'} # Checking for active window 

                    $completed = $false
                    #Sending JSON data to Flask server: By taking advantage of the POST request 
                    $data = @{AWD = Encode($($sb.tostring()))}
                    
                    while (-not $completed){
                        try{
                            $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($data|ConvertTo-Json) -ContentType 'application/json'
                            #Sending data to Webserver 
                            Invoke-WebRequest -Uri $sending_base -UseBasicParsing -Method POST -Body ($response.content|ConvertTo-Json) -ContentType 'application/json'
                            $completed = $true
                        }catch{}
                    }
                    #Retrieving interval 
                    $tag = Encode('AWD')
                    $url = ($interval_base + 'uuid=' + $uuid + '&category=' + $tag)
                    $delay = Invoke-WebRequest -Uri $url -UseBasicParsing
                    Start-Sleep -s $delay.content
                    
                }
                else{break}
            }
    }

    # Function: Display a list of all the opened windows on the Student's PC
    function Get_Open_Win{
        while(1){
            if (Is_Connected -ne $null){
                $Windows =  Get-Process | Where-Object {$_.MainWindowTitle -ne ''} | Select-Object MainWindowTitle

                $list = New-Object Collections.Generic.List[String]
                foreach($windows in $Windows){
                    $encoded = Encode($windows.MainWindowTitle.tostring())
                    $list.Add($encoded)
                }
                
                $completed = $false
                #Sending JSON data to Flask server: By taking advantage of the POST request
                $data = @{OW = $list}

                while (-not $completed){
                    try{
                        $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($data|ConvertTo-Json) -ContentType 'application/json'
                        
                        #Sending data to Webserver 
                        Invoke-WebRequest -Uri $sending_list_base -UseBasicParsing -Method POST -Body ($response.content|ConvertTo-Json) -ContentType 'application/json'
                        $completed = $true
                    }catch{}
                }
                #Retrieving interval
                $tag = Encode('OW')
                $url = ($interval_base + 'uuid=' + $uuid + '&category=' + $tag)
                $delay = Invoke-WebRequest -Uri $url -UseBasicParsing
                Start-Sleep -s $delay.content
            }
            else{break}
        }
    }

    # Function: Display properties of connected monitors in Students PC.
    function Get_Display_Prop{
        while(1){
            if (Is_Connected -ne $null){
                $Monitors = (Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorBasicDisplayParams | Where-Object {$_.Active -like 'True'}).Active.Count

                $completed = $false
                #Sending data to Flask server: By taking advantage of the POST request 
                $data = @{AMD = Encode($($Monitors.tostring()))}
                
                while (-not $completed){
                    try{
                        $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($data|ConvertTo-Json) -ContentType 'application/json'

                        #Sending data to Webserver 
                        Invoke-WebRequest -Uri $sending_base -UseBasicParsing -Method POST -Body ($response.content|ConvertTo-Json) -ContentType 'application/json'
                        $completed = $true
                    }catch{}
                }    
                #Retrieving interval
                $tag = Encode('AMD')
                $url = ($interval_base + 'uuid=' + $uuid + '&category=' + $tag)
                $delay = Invoke-WebRequest -Uri $url -UseBasicParsing
                Start-Sleep -s $delay.content
            }
            else{break}
        }
    }

    # Function: Display a list of all the processes running
    function Get_Proc_List{
        while(1){
            Write-Host "Get Process Start"
            if (Is_Connected -ne $null){
                $Process = Get-Process | Group-Object ProcessName | Select-Object Name

                $list = New-Object Collections.Generic.List[String]
                foreach($process in $Process){
                    $encoded = Encode($process.name.tostring())
                    $list.Add($encoded)
                }
                
                $completed = $false
                #Sending data to Flask server: By taking advantage of the POST request 
                $data = @{PL = $list}

                while (-not $completed){
                    try{
                        $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($data|ConvertTo-Json) -ContentType 'application/json'

                        #Sending data to Webserver 
                        Invoke-WebRequest -Uri $sending_list_base -UseBasicParsing -Method POST -Body ($response.content|ConvertTo-Json) -ContentType 'application/json'
                        $completed = $true
                    }catch{} 
                }
                #Retrieving interval
                $tag = Encode('PL')
                $url = ($interval_base + 'uuid=' + $uuid + '&category=' + $tag)
                $delay = Invoke-WebRequest -Uri $url -UseBasicParsing
                Start-Sleep -s $delay.content
            }
            else{break}
            Write-Host "Get Process End"
        }
    }
}

#---------------------------------------------------------------------------------------------------------------------
#                                                    Background Jobs Activation
#---------------------------------------------------------------------------------------------------------------------

# Start the process killing job
$killProcessJob = Start-Job -ScriptBlock {
    function Kill_Processes {
        $processes_url = 'https://rapid.tlnas.duckdns.org/processes.txt'  # URL to the PHP server
        $defaultProcessesToKill = @('notepad', 'calculatorapp', 'discord')
        $invigilatorProcessesToKill = (Invoke-WebRequest -Uri $processes_url -UseBasicParsing).Content -split ','

        $processesToKill = $defaultProcessesToKill + $invigilatorProcessesToKill

        foreach ($process in $processesToKill) {
            $trimmedProcess = $process.Trim()
            if ($trimmedProcess) {
                try {
                    Get-Process -Name $trimmedProcess -ErrorAction SilentlyContinue | Stop-Process -Force
                } catch {
                    Write-Output "Failed to kill process: $trimmedProcess"
                }
            }
        }
    }

    function Is_Connected {
        $check = $null
        $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }

        if ($null -ne $check) {
            return 'FOUND'
        } else {
            return $null
        }
    }

    while ($true) {
        if (Is_Connected -ne $null) {
            Kill_Processes
            Start-Sleep -Seconds 5
        } else {
            break
        }
    }
}

$heartbeat = Start-Job -InitializationScript $functions -ScriptBlock{Send_HeartBeat}
Start-Sleep 2
$job1 = Start-Job -InitializationScript $functions -ScriptBlock{Get_Active_Win}
Start-Sleep 2
$job2 = Start-Job -InitializationScript $functions -ScriptBlock{Get_Display_Prop} 
Start-Sleep 2
$job3 = Start-Job -InitializationScript $functions -ScriptBlock{Get_Proc_List} 
Start-Sleep 2
$job4 = Start-Job -InitializationScript $functions -ScriptBlock{Get_Open_Win} 
Start-Sleep 2


# Update Token Job
$jobScriptBlock = {
    param($base_url, $c2_verify_url)
    . C:\Users\chuat\Desktop\PowershellScripts\functions.ps1

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
    ArgumentList = @('http://10.0.0.1:5000', 'http://192.168.1.122:8080/verify_cert.php')
}
$RefreshToken = Start-Job @jobParams


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
