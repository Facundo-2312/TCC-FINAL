param(
    [switch]$SkipStage
)

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$StageScript = Join-Path $ScriptDir 'build-package.ps1'
$InstallerScript = Join-Path $ScriptDir 'installer.iss'
$ConfiguredCompiler = $env:INNO_SETUP_COMPILER
$CompilerCandidates = @(
    $ConfiguredCompiler,
    'C:\Program Files (x86)\Inno Setup 6\ISCC.exe',
    'C:\Program Files\Inno Setup 6\ISCC.exe'
) | Where-Object { $_ }

if (-not $SkipStage) {
    powershell -NoProfile -ExecutionPolicy Bypass -File $StageScript
    if ($LASTEXITCODE -ne 0) {
        throw 'Falló la preparación del paquete desktop.'
    }
}

$Compiler = $null
foreach ($candidate in $CompilerCandidates) {
    if (Test-Path $candidate) {
        $Compiler = $candidate
        break
    }
}

if (-not $Compiler) {
    Write-Output 'No se encontró ISCC.exe. El paquete staged ya quedó listo y el script installer.iss está preparado para compilarse manualmente.'
    Write-Output "Script del instalador: $InstallerScript"
    exit 0
}

& $Compiler $InstallerScript
if ($LASTEXITCODE -ne 0) {
    throw "Falló la compilación del instalador con código $LASTEXITCODE"
}

Write-Output 'Instalador compilado correctamente.'
