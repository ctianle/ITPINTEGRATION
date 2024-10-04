# Define proctoring functions into a variable too use it in background jobs
$functions = {
    #---------------------------------------------------------------------------------------------------------------------
    #                                                   Variables Definition
    #---------------------------------------------------------------------------------------------------------------------
    #Web Server for interval tracking
    $interval_base = 'https://24.jubilian.one/interval.php?'
    #Web server for processing string data
    $sending_base = 'https://24.jubilian.one/process.php'
    #Web server for processing list data
    $sending_list_base = 'https://24.jubilian.one/process_list.php'
    #Webserver for getting public key
    $key = 'https://24.jubilian.one/RSA/public_rsa.key'
    #Heartbeat 
    $heartbeat = 'https://24.jubilian.one/ping.php?'

    #---------------------------------------------------------------------------------------------------------------------
    #                                                      Basic Functions
    #---------------------------------------------------------------------------------------------------------------------
    # Function: Determine if Raspberry Pi is connected to the PC
    # Finding for USB with VID 1D6B. VID is the Vendor ID set by us when configuring composite mode on Raspberry Pi
    function Is_Connected{
        $check = $null
        $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }

        if($check -ne $null){
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
            $response = Invoke-WebRequest -Uri http://raspberrypi.local/ -UseBasicParsing -Method POST -Body ($key_data|ConvertTo-Json) -ContentType 'application/json'
            $uuid = ($response.content | ConvertFrom-Json).uuid   
            $completed = $true    
        }
        catch{}
    }
    else{break}

    #---------------------------------------------------------------------------------------------------------------------
    #                                                   Screenshot Functions
    #---------------------------------------------------------------------------------------------------------------------
    function Get_Screenshot{
    	
        [Reflection.Assembly]::LoadWithPartialName("System.Drawing")
        [void] [System.Reflection.Assembly]::LoadWithPartialName("System.Drawing")
        [void] [System.Reflection.Assembly]::LoadWithPartialName("System.Windows.Forms")

    	$width = 0;
    	$height = 0;
    	$workingAreaX = 0;
    	$workingAreaY = 0;
    	
    	$screen = [System.Windows.Forms.Screen]::AllScreens;
    	
    	foreach ($item in $screen)
    	{
    		if($workingAreaX -gt $item.WorkingArea.X)
    		{
    			$workingAreaX = $item.WorkingArea.X;
    		}
    	
    		if($workingAreaY -gt $item.WorkingArea.Y)
    		{
    			$workingAreaY = $item.WorkingArea.Y;
    		}
    	
    		$width = $width + $item.Bounds.Width;
    	
    		if($item.Bounds.Height -gt $height)
    		{
    			$height = $item.Bounds.Height;
    		}
    	}
    	
    	$bounds = [Drawing.Rectangle]::FromLTRB($workingAreaX, $workingAreaY, $width, $height);
    	$bmp = New-Object Drawing.Bitmap $width, $height;
    	$graphics = [Drawing.Graphics]::FromImage($bmp);
    	
    	$graphics.CopyFromScreen($bounds.Location, [Drawing.Point]::Empty, $bounds.size);
    	
    	#$bmp.Save($path);
    	
    	[System.Drawing.Imaging.ImageFormat]$ImageFormat = [System.Drawing.Imaging.ImageFormat]::png;
    	$memory = New-Object System.IO.MemoryStream;
    	$null = $bmp.Save($memory, $ImageFormat);
    	[byte[]]$bytes = $memory.ToArray();
    	$memory.Close();
    	$base64stuff = [System.Convert]::ToBase64String($bytes);
    	#Write-Output $base64stuff; #~3MB Per Screen
    	
    	$graphics.Dispose();
    	$bmp.Dispose();
    	
    	$arr = @{uuid=$uuid;image=$base64stuff;}
    	
    	$sending_ss_base = 'https://24.jubilian.one/process_screenshot.php'
    	Invoke-WebRequest -Uri $sending_ss_base -UseBasicParsing -Method POST -Body ($arr|ConvertTo-Json) -ContentType 'application/json'
    			

    }

    function Call_Get_Screenshot {
        while(1) {
            if (Is_Connected -ne $null) {
                Get_Screenshot
                Start-Sleep 30
            }
            else{break}
        }
    }

}
$job = Start-Job -InitializationScript $functions -ScriptBlock{Call_Get_Screenshot} 
Start-Sleep 2
Wait-Job $job