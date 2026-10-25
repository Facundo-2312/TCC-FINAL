$ErrorActionPreference = 'Stop'

$xamppRoot = Split-Path -Parent (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
$runDir = Join-Path $PSScriptRoot 'run'
$logFile = Join-Path $runDir 'xampp-startup.log'
$mysqlExe = Join-Path $xamppRoot 'mysql\bin\mysqld.exe'
$mysqlConfig = Join-Path $xamppRoot 'mysql\bin\my.ini'
$apacheExe = Join-Path $xamppRoot 'apache\bin\httpd.exe'
$phpExe = Join-Path $xamppRoot 'php\php.exe'
$migrationScript = Join-Path (Split-Path -Parent $PSScriptRoot) 'database\migrate.php'

if (-not (Test-Path $runDir)) {
    New-Item -ItemType Directory -Path $runDir | Out-Null
}

function Test-LocalPort {
    param([int]$Port)

    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $result = $client.BeginConnect('127.0.0.1', $Port, $null, $null)
        if (-not $result.AsyncWaitHandle.WaitOne(500)) {
            return $false
        }
        $client.EndConnect($result)
        return $true
    } catch [System.Net.Sockets.SocketException] {
        return $false
    } finally {
        $client.Close()
    }
}

function Wait-ForLocalPort {
    param(
        [int]$Port,
        [int]$TimeoutSeconds
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        if (Test-LocalPort -Port $Port) {
            return
        }
        Start-Sleep -Seconds 1
    }
    throw "XAMPP no abrió el puerto $Port en $TimeoutSeconds segundos."
}

try {
    Add-Content -LiteralPath $logFile -Value ("{0} Iniciando XAMPP para el usuario actual." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))

    if (-not (Test-LocalPort -Port 3306)) {
        if (-not (Test-Path $mysqlExe) -or -not (Test-Path $mysqlConfig)) {
            throw "No se encontro MySQL o su configuracion en $xamppRoot."
        }
        Start-Process -FilePath $mysqlExe -ArgumentList @("--defaults-file=$mysqlConfig", '--standalone') -WorkingDirectory $xamppRoot -WindowStyle Hidden | Out-Null
        Wait-ForLocalPort -Port 3306 -TimeoutSeconds 60
        Add-Content -LiteralPath $logFile -Value ("{0} MySQL listo en el puerto 3306." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
    } else {
        Add-Content -LiteralPath $logFile -Value ("{0} MySQL ya estaba disponible." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
    }

    if (-not (Test-Path $phpExe) -or -not (Test-Path $migrationScript)) {
        throw 'No se encontro PHP o el actualizador de la base de datos.'
    }
    & $phpExe $migrationScript 2>&1 | ForEach-Object {
        Add-Content -LiteralPath $logFile -Value ([string]$_)
    }
    if ($LASTEXITCODE -ne 0) {
        throw "No se pudo actualizar la base de datos. Revisa $logFile."
    }
    Add-Content -LiteralPath $logFile -Value ("{0} Migraciones de base de datos verificadas." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))

    if (-not (Test-LocalPort -Port 80)) {
        if (-not (Test-Path $apacheExe)) {
            throw "No se encontro Apache en $apacheExe."
        }
        Start-Process -FilePath $apacheExe -WorkingDirectory $xamppRoot -WindowStyle Hidden | Out-Null
        Wait-ForLocalPort -Port 80 -TimeoutSeconds 45
        Add-Content -LiteralPath $logFile -Value ("{0} Apache listo en el puerto 80." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
    } else {
        Add-Content -LiteralPath $logFile -Value ("{0} El puerto 80 ya estaba disponible." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
    }

    $response = Invoke-WebRequest -Uri 'http://127.0.0.1/proj/Login.php' -UseBasicParsing -TimeoutSec 15
    if ([int]$response.StatusCode -ne 200) {
        throw "La aplicacion respondio con HTTP $($response.StatusCode)."
    }
    Add-Content -LiteralPath $logFile -Value ("{0} Aplicacion disponible en http://localhost/proj/." -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
} catch {
    Add-Content -LiteralPath $logFile -Value ("{0} ERROR: {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $_.Exception.Message)
    throw
}
