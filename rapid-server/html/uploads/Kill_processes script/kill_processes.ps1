# kill_processes.ps1
$processes_to_kill = @('notepad', 'calculatorapp', 'chrome', 'discord', 'whatsapp')  # Add process names here

foreach ($process in $processes_to_kill) {
    Get-Process -Name $process -ErrorAction SilentlyContinue | Stop-Process -Force
}
