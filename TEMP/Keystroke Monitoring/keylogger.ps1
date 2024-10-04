while(1)
{
    $PressedKey = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
	$val = (Get-ItemProperty -path 'HKCU:\Software\GetKeypressValue').KeypressValue
    Write-Host $val
}
