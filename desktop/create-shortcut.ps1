function Get-DesktopPath {
    $oneDrive = $env:OneDrive
    if ($oneDrive) {
        $candidates = @(
            (Join-Path $oneDrive 'Escritorio'),
            (Join-Path $oneDrive 'Desktop')
        )

        foreach ($candidate in $candidates) {
            if (Test-Path $candidate) {
                return $candidate
            }
        }
    }

    return [Environment]::GetFolderPath('Desktop')
}

function Ensure-LogoIcon {
    param(
        [string]$ProjectRoot,
        [string]$DesktopDir
    )

    $assetsDir = Join-Path $DesktopDir 'assets'
    if (-not (Test-Path $assetsDir)) {
        New-Item -ItemType Directory -Path $assetsDir | Out-Null
    }

    $sourceLogo = Join-Path $ProjectRoot 'img\logonuevo.jpeg'
    $iconPath = Join-Path $assetsDir 'logo.ico'
    if (-not (Test-Path $sourceLogo)) {
        return $null
    }

    Add-Type -AssemblyName System.Drawing
    $image = [System.Drawing.Image]::FromFile($sourceLogo)

    try {
        $bitmap = New-Object System.Drawing.Bitmap 256, 256
        $graphics = [System.Drawing.Graphics]::FromImage($bitmap)

        try {
            $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
            $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
            $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
            $graphics.Clear([System.Drawing.Color]::Transparent)
            $graphics.DrawImage($image, 0, 0, 256, 256)

            $pngStream = New-Object System.IO.MemoryStream
            $bitmap.Save($pngStream, [System.Drawing.Imaging.ImageFormat]::Png)
            $pngBytes = $pngStream.ToArray()
            $pngStream.Dispose()

            $fileStream = [System.IO.File]::Open($iconPath, [System.IO.FileMode]::Create)
            $writer = New-Object System.IO.BinaryWriter($fileStream)

            try {
                # ICO header + one 256x256 PNG entry.
                $writer.Write([UInt16]0)
                $writer.Write([UInt16]1)
                $writer.Write([UInt16]1)
                $writer.Write([Byte]0)
                $writer.Write([Byte]0)
                $writer.Write([Byte]0)
                $writer.Write([Byte]0)
                $writer.Write([UInt16]1)
                $writer.Write([UInt16]32)
                $writer.Write([UInt32]$pngBytes.Length)
                $writer.Write([UInt32]22)
                $writer.Write($pngBytes)
            } finally {
                $writer.Dispose()
                $fileStream.Dispose()
            }
        } finally {
            $graphics.Dispose()
            $bitmap.Dispose()
        }
    } finally {
        $image.Dispose()
    }

    return $iconPath
}

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$DesktopPath = Get-DesktopPath
$ShortcutPath = Join-Path $DesktopPath 'Restaurante-UY Desktop.lnk'
$TargetPath = Join-Path $ScriptDir 'start-hidden.vbs'
$IconPath = Ensure-LogoIcon -ProjectRoot $ProjectRoot -DesktopDir $ScriptDir

if (-not $IconPath -or -not (Test-Path $IconPath)) {
    $IconPath = Join-Path (Split-Path -Parent (Split-Path -Parent $ScriptDir)) 'xampp-control.exe'
}

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
