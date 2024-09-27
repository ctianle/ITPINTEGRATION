$raspberryPiUrl = "http://10.0.0.1/retrieve_data"
$snapshotsUrl = "http://218.212.196.215/receive_snapshots_data.php"
$screenshotsUrl = "http://218.212.196.215/receive_screenshots_data.php"
$behaviourUrl = "http://218.212.196.215/receive_behaviour_data.php"
$base_url = "http://10.0.0.1"
$key = 'http://218.212.196.215/get_public_key.php'

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

function Is_Connected {
    $check = $null
    $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }

    if ($null -ne $check) {
        return 'FOUND'
    }
}

if (Is_Connected) {
    try {
        $response = Invoke-WebRequest -Uri $base_url -Method POST -Body ($key_data | ConvertTo-Json) -ContentType 'application/json'
        $uuid = ($response.content | ConvertFrom-Json).uuid
        $completed = $true
    }
    catch {
        Write-Host "Failed to get UUID"
    }
}

while ($true) {
    try {
        $response = Invoke-RestMethod -Uri $raspberryPiUrl -Method GET
        if ($response.status -eq "success") {
            foreach ($data in $response.data) {
                # Add UUID to the data
                $data.uuid = $uuid

                # Handle different data types
                if ($data.type -eq "camera image") {
                    $sendResponse = Invoke-RestMethod -Uri $snapshotsUrl -Method POST -Body ($data | ConvertTo-Json) -ContentType "application/json"
                } elseif ($data.type -eq "Screenshot image" -or $data.type -eq "gaze coordinates") {
                    $sendResponse = Invoke-RestMethod -Uri $screenshotsUrl -Method POST -Body ($data | ConvertTo-Json) -ContentType "application/json"
                } else {
                    $sendResponse = Invoke-RestMethod -Uri $behaviourUrl -Method POST -Body ($data | ConvertTo-Json) -ContentType "application/json"
                }
            }
        }
    } catch {
        Write-Host "Error fetching or sending data: $_"
    }
    Start-Sleep -Seconds 10
}
