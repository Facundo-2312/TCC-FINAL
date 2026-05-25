param(
    [string]$HostName = '127.0.0.1',
    [int]$Port = 8030,
    [switch]$NoBrowser
)

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$RunDir = Join-Path $ScriptDir 'run'
$PidFile = Join-Path $RunDir 'php-server.pid'
$LogFile = Join-Path $RunDir 'php-server.log'
$ErrorLogFile = Join-Path $RunDir 'php-server-error.log'
$BundledRuntimeRoot = Join-Path $ProjectRoot 'runtime'
$BundledPhpExe = Join-Path $BundledRuntimeRoot 'php\php.exe'
$ConfiguredXamppRoot = $env:APP_XAMPP_ROOT
$ConfiguredPhpExe = $env:APP_PHP_EXE
$Url = "http://$HostName`:$Port/Login.php"

function Test-PortOpen {
    param(
        [string]$TargetHost,
        [int]$TargetPort
    )

    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $async = $client.BeginConnect($TargetHost, $TargetPort, $null, $null)
        $connected = $async.AsyncWaitHandle.WaitOne(700)
        if (-not $connected) {
            $client.Close()
            return $false
        }
        $client.EndConnect($async) | Out-Null
        $client.Close()
        return $true
    } catch {
        return $false
    }
}

function Get-RunningServerProcess {
    if (-not (Test-Path $PidFile)) {
        return $null
    }

    $pidText = (Get-Content -Path $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1)
    if (-not $pidText) {
        return $null
    }

    $pidValue = 0
    if (-not [int]::TryParse($pidText, [ref]$pidValue)) {
        return $null
    }

    return Get-Process -Id $pidValue -ErrorAction SilentlyContinue
}

function Get-EdgeExecutable {
    $candidates = @(
        (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe')
    )

    foreach ($candidate in $candidates) {
        if ($candidate -and (Test-Path $candidate)) {
            return $candidate
        }
    }

    return $null
}

function Get-XamppRoot {
    if ($ConfiguredXamppRoot -and (Test-Path $ConfiguredXamppRoot)) {
        return $ConfiguredXamppRoot
    }

    $candidates = @(
        (Split-Path -Parent (Split-Path -Parent $ProjectRoot)),
        'C:\xampp'
    )

    foreach ($candidate in $candidates) {
        if ($candidate -and (Test-Path (Join-Path $candidate 'php\php.exe'))) {
            return $candidate
        }
    }

    return $null
}

function Get-PhpExecutable {
    if ($ConfiguredPhpExe -and (Test-Path $ConfiguredPhpExe)) {
        return $ConfiguredPhpExe
    }

    if (Test-Path $BundledPhpExe) {
        return $BundledPhpExe
    }

    $xamppRoot = Get-XamppRoot
    if ($xamppRoot) {
        $candidate = Join-Path $xamppRoot 'php\php.exe'
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    return $null
}

if (-not (Test-Path $RunDir)) {
    New-Item -ItemType Directory -Path $RunDir | Out-Null
}

$XamppRoot = Get-XamppRoot
$PhpExe = Get-PhpExecutable
$MysqlStart = if ($XamppRoot) { Join-Path $XamppRoot 'mysql_start.bat' } else { $null }

if (-not $PhpExe) {
    throw 'No se encontró un ejecutable PHP válido. Configura APP_PHP_EXE o APP_XAMPP_ROOT si usas otra instalación.'
}

$serverProcess = Get-RunningServerProcess
if (-not $serverProcess) {
    $serverProcess = Start-Process -FilePath $PhpExe -ArgumentList @('-S', "$HostName`:$Port", '-t', $ProjectRoot) -WorkingDirectory $ProjectRoot -RedirectStandardOutput $LogFile -RedirectStandardError $ErrorLogFile -WindowStyle Hidden -PassThru
    if (-not $serverProcess) {
        throw 'No se pudo iniciar el servidor PHP embebido.'
    }
    Set-Content -Path $PidFile -Value $serverProcess.Id -Encoding ASCII
}

if (-not (Test-PortOpen -TargetHost '127.0.0.1' -TargetPort 3306) -and (Test-Path $MysqlStart)) {
    Start-Process -FilePath $MysqlStart -WorkingDirectory $XamppRoot -WindowStyle Hidden | Out-Null
}

if (-not $NoBrowser) {
    $edgeExe = Get-EdgeExecutable
    if ($edgeExe) {
        Start-Process -FilePath $edgeExe -ArgumentList @("--app=$Url", '--window-size=1440,940') | Out-Null
    } else {
        Start-Process -FilePath $Url | Out-Null
    }
}

Write-Output "Desktop app lista en $Url"
