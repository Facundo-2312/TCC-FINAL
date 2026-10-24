# Prueba de RESTAURANTE-UY en InfinityFree

## 1. Crear la base

En phpMyAdmin, selecciona la base de datos vacia creada en el panel y usa **Importar**:

1. Importa `SQL/install_infinityfree.sql`.
2. Importa `SQL/catalogo_infinityfree.sql`.

Estos archivos no borran ni crean bases de datos. No importes `SQL/install.sql`: es para una instalacion local limpia y contiene sentencias no compatibles con el hosting gratuito.

## 2. Subir los archivos

Sube el contenido del proyecto al directorio `htdocs` del subdominio. Incluye `.htaccess`; protege `.env`, SQL, los scripts de preparacion y los logs.
InfinityFree distingue mayusculas y minusculas en nombres de archivos: conserva exactamente nombres como `CAJA.php`.

## 3. Configurar la conexion

Copia `.env.example` como `.env` en la raiz de `htdocs` y completa los datos de MySQL que muestra el panel:

```ini
APP_ENV=production
APP_BASE_PATH=
APP_DB_HOST=HOST_MYSQL_DEL_PANEL
APP_DB_USER=USUARIO_MYSQL_DEL_PANEL
APP_DB_PASSWORD=CONTRASENA_MYSQL_DEL_PANEL
APP_DB_NAME=NOMBRE_COMPLETO_DE_LA_BASE
APP_DB_CHARSET=utf8mb4
APP_DB_STOCK_TRIGGERS=0
```

No subas ni compartas el `.env` real. `APP_DB_STOCK_TRIGGERS=0` activa en PHP el descuento y la reposicion de stock, porque InfinityFree gratuito no permite crear triggers.

## 4. Crear una cuenta administradora de pruebas

1. Abre `https://TU_SUBDOMINIO/registro_cliente.php` y registra una cuenta con una contrasena unica para las pruebas.
2. En phpMyAdmin, abre la pestaña SQL y ejecuta la consulta siguiente sustituyendo `USUARIO_ADMIN_DE_PRUEBA` por ese usuario:

```sql
UPDATE usuarios
SET id_rol = (SELECT id_rol FROM roles WHERE nombre = 'Administrador' LIMIT 1)
WHERE usuario = 'USUARIO_ADMIN_DE_PRUEBA';
```

3. Inicia sesion en `https://TU_SUBDOMINIO/Login.php` con esa cuenta.
4. Para probar la experiencia del cliente, usa el acceso de cliente de la pagina de inicio y registra una segunda cuenta de prueba.

No uses credenciales de produccion. Cuando finalice la evaluacion, elimina la base de pruebas desde el panel si ya no la necesitas.

## 5. Diagnosticar errores HTTP 500

En produccion la aplicacion oculta el detalle del error en pantalla. Si una solicitud falla, consulta `storage/logs/security.log` desde el Administrador de archivos y busca la entrada `event=error_php` mas reciente. Comparte solo esa linea para recibir ayuda; no publiques el archivo de registro, porque puede contener datos de configuracion del servidor. Si no aparece una entrada nueva, revisa el registro de errores PHP que ofrezca el panel del hosting.
