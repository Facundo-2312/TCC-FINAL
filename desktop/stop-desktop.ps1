$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RunDir = Join-Path $ScriptDir 'run'
$PidFile = Join-Path $RunDir 'php-server.pid'

if (-not (Test-Path $PidFile)) {
    Write-Output 'No hay servidor desktop en ejecución.'
    exit 0
}

$pidText = (Get-Content -Path $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1)
$pidValue = 0
if ($pidText -and [int]::TryParse($pidText, [ref]$pidValue)) {
    $process = Get-Process -Id $pidValue -ErrorAction SilentlyContinue
    if ($process) {
        Stop-Process -Id $pidValue -Force
        Write-Output "Servidor detenido (PID $pidValue)."
    } else {
        Write-Output 'El proceso ya no estaba activo.'
    }
}

Remove-Item -Path $PidFile -ErrorAction SilentlyContinue
