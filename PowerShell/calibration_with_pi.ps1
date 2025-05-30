Add-Type -AssemblyName PresentationFramework

# Endpoint to send the public key
$calibration_url = "http://10.0.0.1/get_calibration" 
$resolution_url = "http://10.0.0.1/update_resolution"


$Position = -1
$PiReady = $false

# Get all video controllers
$displays = Get-CimInstance Win32_VideoController

# Filter for the active display (non-null resolution)
$activeDisplay = $displays | Where-Object {
    $_.CurrentHorizontalResolution -ne $null -and $_.CurrentVerticalResolution -ne $null
}

$actual_width = $activeDisplay.CurrentHorizontalResolution -as [int]
$actual_height = $activeDisplay.CurrentVerticalResolution -as [int]

function New-Window {
    [xml]$xaml = @"
<Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
        Title="Circle Movement" WindowState="Maximized" WindowStyle="None" Topmost="True">
    <Canvas Name="canvas">
        <Ellipse Name="circle" Fill="Blue" Width="40" Height="40"/>
        <TextBlock Name="textBlock" Text="Look at the circle" FontSize="30" Foreground="Red"/>
    </Canvas>
</Window>
"@

    $reader = (New-Object System.Xml.XmlNodeReader $xaml)
    $window = [Windows.Markup.XamlReader]::Load($reader)

    return $window
}

# Function to move the circle
function Move-Circle {
    param (
        [System.Windows.Controls.Canvas]$canvas,
        [System.Windows.Shapes.Ellipse]$circle,
        [int]$width,
        [int]$height,
        [int]$radius,
        [int]$position
    )

    Write-Output "move circle called"

    switch ($position) {
        0 {
            $newX = 0
            $newY = 0
        }
        1 {
            $newX = $width - 2 * $radius
            $newY = 0
        }
        2 {
            $newX = $width - 2 * $radius
            $newY = $height - 2 * $radius
        }
        3 {
            $newX = 0
            $newY = $height - 2 * $radius
        }
        4 {
            return
        }
        5 {
            $window.Dispatcher.Invoke({ $window.Close() })
            return
        }
    }

    Write-Output "Position: $position, X: $newX, Y: $newY"

    $canvas.Dispatcher.Invoke([System.Windows.Threading.DispatcherPriority]::Render, [Action]{
        [System.Windows.Controls.Canvas]::SetLeft($circle, $newX)
        [System.Windows.Controls.Canvas]::SetTop($circle, $newY)
    })
}

# Main function
function Main {
    while (-not $PiReady) {
        try {
            $response = Invoke-RestMethod -Uri $calibration_url -Method Get
            if ($response.Status -ne $null) {
                $PiReady = $response.Status
                #Write-Output $response
            }
        } catch {
            Write-Output "Error fetching status: $_"
        }
        Start-Sleep -Seconds 1
    }
	
    $window = New-Window
    $canvas = $window.FindName("canvas")
    $circle = $window.FindName("circle")
    $textBlock = $window.FindName("textBlock")

    $width = [System.Windows.SystemParameters]::PrimaryScreenWidth
    $height = [System.Windows.SystemParameters]::PrimaryScreenHeight
    $radius = 20

    # Position the text at the top middle
    $textWidth = 300  # Approximate width of the text block
    $textHeight = 50  # Approximate height of the text block
    $canvas.Dispatcher.Invoke([System.Windows.Threading.DispatcherPriority]::Render, [Action]{
        [System.Windows.Controls.Canvas]::SetLeft($textBlock, ($width / 2) - ($textWidth / 2))
        [System.Windows.Controls.Canvas]::SetTop($textBlock, 10)  # Set a small value for top margin
    })
	
	$payload = @{
    "Width"  = $actual_width
    "Height" = $actual_height
	
	}
	# Convert payload to JSON
	$jsonPayload = $payload | ConvertTo-Json

	# Send the resolution data to the endpoint using POST
	try {
		$response = Invoke-RestMethod -Uri $resolution_url -Method Post -Body $jsonPayload -ContentType "application/json"
		Write-Output "Server Response: $($response | ConvertTo-Json)"
	} catch {
		Write-Output "Error sending resolution: $_"
	}
	

    # Show the window
    $window.Show()

    while ($Position -lt 4) {
        try {
            $response = Invoke-RestMethod -Uri $calibration_url -Method Get
            Write-Output $response
            if ($response.Position -ne $null) {
                Write-Output $response
                if ($Position -ne $response.Position) {
                    $Position = $response.Position
                    Write-Output "position changed $Position"
                    Move-Circle -canvas $canvas -circle $circle -width $width -height $height -radius $radius -position $Position
                    $canvas.Dispatcher.Invoke([System.Windows.Threading.DispatcherPriority]::Render, [Action]{})
                }
            }
            Start-Sleep -Seconds 1
        } catch {
            Write-Output "Error fetching status: $_"
        }
    }
    $window.Close()
}

Main
