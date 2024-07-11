Add-Type -AssemblyName PresentationFramework

# Endpoint to send the public key
$url = "http://192.168.18.5/get_calibration"  # Change to 10.0.0.1 later

$Position = -1
$PiReady = $false

# Function to create a WPF window
function New-Window {
    [xml]$xaml = @"
<Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
        Title="Circle Movement" WindowState="Maximized" WindowStyle="None" Topmost="True">
    <Canvas Name="canvas">
        <Ellipse Name="circle" Fill="Blue" Width="40" Height="40"/>
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
            $newX = $width / 2 - $radius
            $newY = $height / 2 - $radius
        }
        1 {
            $newX = 0
            $newY = 0
        }
        2 {
            $newX = $width - 2 * $radius
            $newY = 0
        }
        3 {
            $newX = $width - 2 * $radius
            $newY = $height - 2 * $radius
        }
        4 {
            $newX = 0
            $newY = $height - 2 * $radius
        }
        5 {
            return
        }
        6 {
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
            $response = Invoke-RestMethod -Uri $url -Method Get
            if ($response.Status -ne $null) {
                $PiReady = $response.Status
                Write-Output $response
            }
        } catch {
            Write-Output "Error fetching status: $_"
        }
        Start-Sleep -Seconds 1
    }

    $window = New-Window
    $canvas = $window.FindName("canvas")
    $circle = $window.FindName("circle")

    $width = [System.Windows.SystemParameters]::PrimaryScreenWidth
    $height = [System.Windows.SystemParameters]::PrimaryScreenHeight
    $radius = 20

    # Show the window
    $window.Show()

    while ($Position -lt 6) {
        try {
            $response = Invoke-RestMethod -Uri $url -Method Get
            Write-Output $response
            if ($response.Position -ne $null) {
                Write-Output $response
                if ($Position -ne $response.Position) {
                    $Position = $response.Position
                    Write-Output "position changed"
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
