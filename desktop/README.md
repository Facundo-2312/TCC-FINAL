# Desktop runtime para Windows

Esta carpeta convierte la aplicación PHP en una experiencia de escritorio ligera para Windows usando el PHP de XAMPP y una ventana de navegador en modo app.

## Qué hace

- Levanta un servidor PHP local en `http://127.0.0.1:8030`
- Reutiliza la base de datos actual
- Abre la aplicación en Microsoft Edge en modo ventana si Edge está instalado
- Permite iniciar y detener la app con doble clic

## Archivos principales

- `start-desktop.bat`: inicia la app desktop
- `stop-desktop.bat`: detiene el servidor local
- `start-hidden.vbs`: inicia la app sin mostrar consola
- `create-shortcut.ps1`: crea un acceso directo en el escritorio

## Requisitos

- XAMPP instalado en `C:\xampp`
- Proyecto ubicado en `C:\xampp\htdocs\proj`
- Base de datos disponible en MySQL/MariaDB

## Uso rápido

1. Haz doble clic en `start-desktop.bat` o `start-hidden.vbs`.
2. Se abrirá la aplicación en una ventana separada.
3. Para cerrar el servidor local, ejecuta `stop-desktop.bat`.

## Generar paquete instalable

1. Ejecuta `build-package.ps1` para preparar una copia distribuible de la app en `desktop/build/app`.
2. Ejecuta `build-installer.ps1` para intentar compilar el instalador.
3. Si Inno Setup no está instalado, `build-installer.ps1` deja listo el archivo `installer.iss` para compilarlo manualmente más adelante.

Archivos de build:

- `build-package.ps1`: arma el paquete staged y copia un runtime PHP dentro de `runtime/php`
- `build-installer.ps1`: busca `ISCC.exe`, ejecuta el staging y compila si el compilador existe
- `installer.iss`: script de Inno Setup para generar el instalador de Windows

## Acceso directo en el escritorio

Ejecuta:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\create-shortcut.ps1
```

Esto crea un acceso directo llamado `Restaurante-UY Desktop` en el escritorio del usuario actual.

## Configuración de base de datos

La app ya soporta variables de entorno desde `app_bootstrap.php`:

- `APP_DB_HOST`
- `APP_DB_USER`
- `APP_DB_PASSWORD`
- `APP_DB_NAME`
- `APP_DB_CHARSET`

## Configuración del runtime desktop

Si tu XAMPP o tu PHP no están en la ruta esperada, puedes definir:

- `APP_XAMPP_ROOT`
- `APP_PHP_EXE`

Si en el futuro quieres empaquetar esto como instalador `.exe`, la base correcta ya está preparada para ese paso.
