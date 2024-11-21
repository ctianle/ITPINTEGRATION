# Main script execution
$scriptBlock = {
    # Define the temporary folder path
    $tempFolderPath = [System.IO.Path]::Combine([System.IO.Path]::GetTempPath(), "tempImages")
    if (-not (Test-Path -Path $tempFolderPath)) {
        New-Item -ItemType Directory -Path $tempFolderPath
    }
    Write-Output "Temporary images folder created: $tempFolderPath"

    # Define the Flask server URL
    $flaskUrl = "http://10.0.0.1/upload"
    Write-Output "Flask server URL: $flaskUrl"

    # Queue to hold file paths
    $fileQueue = [System.Collections.Queue]::Synchronized([System.Collections.Queue]::new())
    Write-Output "File queue initialized."

    # Function to process files from the queue
    function Process-FileQueue {
        while ($fileQueue.Count -gt 0) {
            $filePath = $fileQueue.Dequeue()

            # Check if the file exists to avoid errors
            if (Test-Path $filePath) {
                try {
                    # Read the image file as a byte array
                    $imageBytes = [System.IO.File]::ReadAllBytes($filePath)

                    # Convert the byte array to a base64 string
                    $imageBase64 = [Convert]::ToBase64String($imageBytes)

                    # Create the JSON payload
                    $jsonPayload = @{
                        "image_name" = [System.IO.Path]::GetFileName($filePath)
                        "image_data" = $imageBase64
                    } | ConvertTo-Json

                    # Send the JSON payload to the Flask server
                    $response = Invoke-RestMethod -Uri $flaskUrl -Method Post -ContentType "application/json" -Body $jsonPayload

                    # Delete the processed file
                    Remove-Item -Path $filePath -Force
                } catch {
                    Write-Output "Error processing file: $filePath. $_"
                }
            }
        }
    }

    # Polling function to check the directory for new files
    function Poll-Directory {
        $existingFiles = @()
        while ($true) {
            $currentFiles = Get-ChildItem -Path $tempFolderPath -Filter *.jpg
            $newFiles = $currentFiles | Where-Object { $_.FullName -notin $existingFiles }

            foreach ($file in $newFiles) {
                $existingFiles += $file.FullName
                $fileQueue.Enqueue($file.FullName)
            }

            Process-FileQueue
            Start-Sleep -Seconds 1
        }
    }

    # Cleanup function to delete the temporary folder when the script ends
    function Cleanup {
        if (Test-Path -Path $tempFolderPath) {
            Remove-Item -Path $tempFolderPath -Recurse -Force
        }
    }

    # Register the cleanup function to run on script exit
    $script:CleanupJob = Register-EngineEvent -SourceIdentifier "PowerShell.Exiting" -Action { Cleanup } -SupportEvent
    Write-Output "Cleanup function registered."

    # Start polling the directory
    Poll-Directory

    # Keep the script running
    try {
        while ($true) {
            Start-Sleep -Seconds 10
        }
    } finally {
        # Unregister the event when done
        Unregister-Event -SourceIdentifier $script:CleanupJob.Name
        Cleanup
    }
}

# Start the monitoring script as a background job
$job = Start-Job -ScriptBlock $scriptBlock
Write-Output "Background job started with ID: $($job.Id)"
Write-Output "To monitor the job, use Get-Job and Receive-Job commands."
