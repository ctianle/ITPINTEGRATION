# Define proctoring functions into a variable too use it in background jobs
$scriptBlock = {
    #---------------------------------------------------------------------------------------------------------------------
    #                                                   Variables Definition
    #---------------------------------------------------------------------------------------------------------------------
    #Webserver for getting public key
	$base_url = "http://10.0.0.1"
	$key = 'http://218.212.196.215/get_public_key.php'

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
	$tempFolderPath = [System.IO.Path]::Combine([System.IO.Path]::GetTempPath(), "tempImages")
    if (-not (Test-Path -Path $tempFolderPath)) {
        New-Item -ItemType Directory -Path $tempFolderPath
    }
	else
	{
		Write-Host "File path exists"	
	}
	
	try {
		$pub_key = Invoke-WebRequest -Uri $key -UseBasicParsing
		Write-Host "Retrieved public key from web server." -ForegroundColor Green
	} catch {
		Write-Error "Failed to retrieve public key from web server: $_"
		return
	}


    # Sending public key to Pi's Flask server in JSON format            
    $key_data = @{PuK = Encode($($pub_key.tostring()))}
    # Result is the UUID 
    $completed = $false

    if (Is_Connected -ne $null){
        try{
            $response = Invoke-WebRequest -Uri $base_url -UseBasicParsing -Method POST -Body ($key_data|ConvertTo-Json) -ContentType 'application/json'
            $uuid = ($response.content | ConvertFrom-Json).uuid   
            $completed = $true
			Write-Host "Connected"
        }
        catch{
			Write-Error "Error sending public key: $_"
		}
    }
    else{
		break
		}

    #---------------------------------------------------------------------------------------------------------------------
    #                                                   Screenshot Functions
    #---------------------------------------------------------------------------------------------------------------------
    function Get_Screenshot{
    	Write-Host "Call Get Screenshot"
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
    	
        # --------------------------Saving screenshot to folder-------------------------------------
        # Define the image format
        [System.Drawing.Imaging.ImageFormat]$ImageFormat = [System.Drawing.Imaging.ImageFormat]::Jpeg

        # Generate a unique filename using timestamp
        $timestamp = Get-Date -Format "yyyyMMdd_HHmmss_fff"
        $fileName = "screenshot_$timestamp.jpg"
        $filePath = Join-Path -Path $tempFolderPath -ChildPath $fileName

        # Save the screenshot to the tempImages folder
        $bmp.Save($filePath, $ImageFormat)
    	
    	$graphics.Dispose();
    	$bmp.Dispose();

    }


	Write-Host "Reach main loop"
	while(1) {
		if (Is_Connected -ne $null) {
			Get_Screenshot
			Write-Host "Get Screenshot"
			Start-Sleep 30
		}
		else{
			Write-Host "Stopped"
			break}
	}
    

}
$job = Start-Job -ScriptBlock $scriptBlock
Write-Output "Background job started with ID: $($job.Id)"
Write-Output "To monitor the job, use Get-Job and Receive-Job commands."
