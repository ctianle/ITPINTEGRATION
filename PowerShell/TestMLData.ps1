$raspberryPiUrl = "http://10.0.0.1/retrieve_data"
$snapshotsUrl = "http://218.212.196.215/receive_snapshots_data.php"
$screenshotsUrl = "http://218.212.196.215/receive_screenshots_data.php"
$behaviourUrl = "http://218.212.196.215/receive_behaviour_data.php"
$base_url = "http://10.0.0.1"
$key = 'http://218.212.196.215/get_public_key.php'

while ($true) {
    try {
        $response = Invoke-RestMethod -Uri $raspberryPiUrl -Method GET
        if ($response.status -eq "success") {
            foreach ($data in $response.data) {

                # Handle different data types
                if ($data.type -eq "camera image") {
                    $sendResponse = Invoke-RestMethod -Uri $snapshotsUrl -Method POST -Body ($data.content | ConvertTo-Json) -ContentType "application/json"
                } elseif ($data.type -eq "Screenshot image" -or $data.type -eq "gaze coordinates") {
                    $sendResponse = Invoke-RestMethod -Uri $screenshotsUrl -Method POST -Body ($data.content | ConvertTo-Json) -ContentType "application/json"
                } else {
                    $sendResponse = Invoke-RestMethod -Uri $behaviourUrl -Method POST -Body ($data.content | ConvertTo-Json) -ContentType "application/json"
                }
            }
        }
    } catch {
        Write-Host "Error fetching or sending data: $_"
    }
    Start-Sleep -Seconds 10
}
