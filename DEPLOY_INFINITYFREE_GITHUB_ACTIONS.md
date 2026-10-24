# TCC-FINAL — despliegue automático en InfinityFree con GitHub Actions

Este flujo publica archivos PHP/JS/CSS y recursos de la aplicación mediante **FTPS con TLS y validación de certificado**. No instala MySQL, no importa SQL, no sube `.env` y **no borra archivos remotos**.

## 1. Preparar InfinityFree
- En el panel de tu cuenta activa, abrí **FTP Details** y anotá el **FTP Username** y la contraseña FTP (no compartirla).
- El servidor suele ser `ftpupload.net`, pero verificá lo indicado en el panel.
- En **Online File Manager**, identificá la ruta FTP que termina en `/htdocs/`. En cuentas con varios dominios puede ser `/tu-dominio/htdocs/` en lugar de `/htdocs/`.
- Confirmá que la base MySQL ya tenga el esquema y catálogo. **No** ejecutar scripts SQL de instalación sobre una base con datos.

## 2. Crear el environment y los secretos de GitHub
Abrí **Settings → Environments → New environment**, nombre exacto `infinityfree`.
Opcionalmente agregá *required reviewers* para aprobar cada despliegue.

Dentro de ese environment:
- **Environment secrets**: `INFINITY_FTP_USERNAME` = usuario FTP real del panel.
- **Environment secrets**: `INFINITY_FTP_PASSWORD` = contraseña FTP real del panel.
- **Environment variables**: `INFINITY_FTP_REMOTE_DIR` = ruta remota real terminada en `/htdocs/`, por ejemplo `/htdocs/`.
- **Environment variables**: `INFINITY_FTP_HOST` = hostname FTP real; opcional, por defecto `ftpupload.net`.

Los nombres deben coincidir exactamente. Nunca colocar credenciales en YAML, `.env.example`, issues o PR.

## 3. Crear .env únicamente en el hosting
En el directorio web de InfinityFree, creá un archivo `.env` privado con:
```ini
APP_ENV=production
APP_TIMEZONE=America/Montevideo
APP_BASE_PATH=
APP_DB_HOST=HOST_MYSQL_REAL
APP_DB_USER=USUARIO_MYSQL_REAL
APP_DB_PASSWORD=CONTRASENA_PRIVADA
APP_DB_NAME=NOMBRE_COMPLETO_BASE
APP_DB_CHARSET=utf8mb4
APP_DB_STOCK_TRIGGERS=0
```
El `.htaccess` del repositorio bloquea archivos que comienzan con punto; comprobá desde el navegador que `/.env` **no sea descargable**. Si no podés asegurar esto, no habilites el sitio ni guardes allí la contraseña.

## 4. Primera publicación
- El workflow `Deploy PHP to InfinityFree (FTPS)` se activa con **push a main** después de fusionar el PR; no se ejecuta automáticamente desde el PR.
- También admite **Actions → Deploy PHP to InfinityFree (FTPS) → Run workflow** para publicación manual (seleccioná `main` una vez fusionado).
- No usa `--delete` ni sincronización destructiva. Los archivos que se hayan eliminado de Git pueden quedar en el servidor: revisar y retirar manualmente los obsoletos.
- Conserva las imágenes existentes en `MVCsix1.0/files`. Copia únicamente las imágenes iniciales que faltan mediante `--only-missing` y actualiza el `.htaccess` que impide ejecutar scripts en esa carpeta. No sobrescribe ni elimina imágenes cargadas por usuarios.
- El punto de entrada raíz `index.php` redirige a `Principal.php`.

## 5. Pruebas
- Abrir `https://TU-DOMINIO/` y `/Login.php`.
- Probar `registro_cliente.php`, `cliente.php`, cocina, mozo, caja, imágenes y operaciones de base.
- Confirmar que `/.env`, `/SQL/` y `/src/` devuelven 403/404; no deben revelar datos.
- Confirmar que los cambios se reflejan después de un commit aprobado en `main`.
- Si el hosting rechaza FTPS/certificados o limita transferencias, revisar el log de Actions **sin desactivar la validación TLS**.

## Precauciones
- El flujo no crea credenciales ni una URL. Requiere una cuenta InfinityFree ya activa.
- No publicar datos reales de clientes, respaldos SQL ni credenciales de XAMPP.
- En el hosting gratuito existen límites de tamaño por archivo; revisar logs y archivos grandes.
- Antes de fusionar, revisar los archivos staged, exclusiones y rutas FTP con la orientadora o responsable técnico.
