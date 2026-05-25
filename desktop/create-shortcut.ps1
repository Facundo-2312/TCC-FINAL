$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$DesktopPath = [Environment]::GetFolderPath('Desktop')
$ShortcutPath = Join-Path $DesktopPath 'Restaurante-UY Desktop.lnk'
$TargetPath = Join-Path $ScriptDir 'start-hidden.vbs'
$IconPath = Join-Path (Split-Path -Parent (Split-Path -Parent $ScriptDir)) 'xampp-control.exe'

$WshShell = New-Object -ComObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = $TargetPath
$Shortcut.WorkingDirectory = $ScriptDir
if (Test-Path $IconPath) {
    $Shortcut.IconLocation = "$IconPath,0"
}
$Shortcut.Description = 'Abre Restaurante-UY en modo desktop'
$Shortcut.Save()

Write-Output "Acceso directo creado en: $ShortcutPath"
