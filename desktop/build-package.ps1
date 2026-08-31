param(
    [string]$OutputRoot
)

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$DefaultOutputRoot = Join-Path $ScriptDir 'build\app'
$OutputRoot = if ($OutputRoot) { $OutputRoot } else { $DefaultOutputRoot }
$ConfiguredXamppRoot = $env:APP_XAMPP_ROOT
$XamppRoot = if ($ConfiguredXamppRoot) { $ConfiguredXamppRoot } else { 'C:\xampp' }
$PhpSource = Join-Path $XamppRoot 'php'

if (-not (Test-Path $PhpSource)) {
    throw "No se encontró la carpeta PHP de XAMPP en $PhpSource"
}

if (Test-Path $OutputRoot) {
    Remove-Item -Path $OutputRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $OutputRoot | Out-Null

$excludedTopLevel = @('.git', '.vscode', 'desktop', '.env')
$topLevelItems = Get-ChildItem -Path $ProjectRoot -Force
foreach ($item in $topLevelItems) {
    if ($excludedTopLevel -contains $item.Name) {
        continue
    }

    $destination = Join-Path $OutputRoot $item.Name
    Copy-Item -Path $item.FullName -Destination $destination -Recurse -Force
}

$desktopSource = Join-Path $ProjectRoot 'desktop'
$desktopDestination = Join-Path $OutputRoot 'desktop'
New-Item -ItemType Directory -Path $desktopDestination | Out-Null

$desktopItems = Get-ChildItem -Path $desktopSource -Force
foreach ($desktopItem in $desktopItems) {
    if ($desktopItem.Name -in @('build', 'run')) {
        continue
    }

    $desktopItemDestination = Join-Path $desktopDestination $desktopItem.Name
    Copy-Item -Path $desktopItem.FullName -Destination $desktopItemDestination -Recurse -Force
}

$stageLogDir = Join-Path $OutputRoot 'storage\logs'
if (Test-Path $stageLogDir) {
    Get-ChildItem -Path $stageLogDir -Filter '*.log' -File | Remove-Item -Force
}

$StageRunDir = Join-Path $OutputRoot 'desktop\run'
if (Test-Path $StageRunDir) {
    Remove-Item -Path $StageRunDir -Recurse -Force
}
New-Item -ItemType Directory -Path $StageRunDir | Out-Null

$RuntimeRoot = Join-Path $OutputRoot 'runtime'
$PhpDestination = Join-Path $RuntimeRoot 'php'
if (Test-Path $PhpDestination) {
    Remove-Item -Path $PhpDestination -Recurse -Force
}
New-Item -ItemType Directory -Path $RuntimeRoot | Out-Null
Copy-Item -Path $PhpSource -Destination $PhpDestination -Recurse -Force

Write-Output "Paquete desktop preparado en: $OutputRoot"
Write-Output "Runtime PHP embebido copiado en: $PhpDestination"
