# Prueba de RESTAURANTE-UY en InfinityFree

## 1. Crear la base

En phpMyAdmin, selecciona la base de datos vacia creada en el panel y usa **Importar**:

1. Importa `SQL/install_infinityfree.sql`.
2. Importa `SQL/catalogo_infinityfree.sql`.

Estos archivos no borran ni crean bases de datos. No importes `SQL/install.sql`: es para una instalacion local limpia y contiene sentencias no compatibles con el hosting gratuito.

### Si ya importaste la base y los usuarios no coinciden con su rol

Comprueba primero los datos reales, sin modificar las cuentas:

```sql
SELECT u.usuario, u.id_rol, r.nombre AS rol
FROM usuarios u
LEFT JOIN roles r ON r.id_rol = u.id_rol
ORDER BY u.id_usuario;
```

Si los numeros y nombres ya coinciden, no ejecutes la reparacion ni vuelvas a
importar la base. Un error HTTP 500 durante el login tambien puede deberse a la
conexion, no a los roles.

El sistema identifica los roles por numero (1 Administrador, 2 Caja, 3 Mozo, 4 Cocina, 5 Cliente). Las versiones anteriores de `install_infinityfree.sql` dejaban que MySQL numerara los roles y el hosting puede asignar otros numeros (por ejemplo 1, 11, 21...). En ese caso cada cuenta se trata con un rol equivocado o el login responde "Tu usuario no tiene un rol valido".

Ejecuta una vez `SQL/reparar_roles_infinityfree.sql` en phpMyAdmin (pestana **SQL** o **Importar**). Renumera los roles segun su nombre, conserva el rol real de cada usuario y empleado y puede repetirse sin riesgo. La consulta final muestra cada usuario junto a su rol para comprobarlo.

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

Deja `APP_BASE_PATH=` vacio: la aplicacion detecta sola si esta en la raiz de `htdocs` o en una subcarpeta. Si lo completas (por ejemplo con `/proj` copiado de tu PC) y la carpeta no existe en el hosting, todos los enlaces apuntaran a una ruta inexistente.

La configuracion se consulta con `App\Support\Env::get()`, que conserva los valores
de `.env` aunque el hosting no permita modificarlos con `putenv()`. No reemplaces
estas llamadas por `getenv()`: se perderian la conexion MySQL y el manejo de stock
sin triggers. Para comprobar esta compatibilidad localmente:

```powershell
C:\xampp\php\php.exe database\tests\env_url_test.php
C:\xampp\php\php.exe -d disable_functions=putenv database\tests\env_url_test.php
```

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

Si el error de MySQL dice `No such file or directory`, comprueba que la aplicacion
este leyendo `APP_DB_HOST` desde `.env`, en vez de intentar una conexion local.
Si dice `Access denied`, verifica la contrasena MySQL contra la del panel de la
cuenta de hosting. No es la contrasena de un usuario del restaurante. No copies
credenciales al codigo ni al repositorio, y conserva el `.env` del hosting al
subir futuras actualizaciones.

## 6. Bloqueo 403 del asistente

InfinityFree responde 403 a cualquier URL que contenga `chat`, porque prohibe los
chats en vivo que consultan el servidor continuamente. El asistente del sistema no
es un chat en vivo: responde preguntas frecuentes con una sola peticion por
consulta y sin sondeo. Por eso sus archivos publicos se llaman `asistente.js` y
`asistente_api.php`, el cambio de nombre que recomienda el propio proveedor.
No vuelvas a usar `chat` en nombres de archivos, carpetas ni parametros de URL, y
borra del hosting los antiguos `chatbot.js` y `chatbot_api.php` si siguen alli.

Referencia: [explicacion del bloqueo en el foro de InfinityFree](https://forum.infinityfree.com/t/error-403-on-a-single-page/82186).

## 7. Importaciones que perdieron AUTO_INCREMENT

Si las altas de usuarios, productos, pedidos o pagos fallan con
`Duplicate entry '0' for key 'PRIMARY'`, comprueba la columna `Extra` en la
estructura de la tabla. La clave numerica debe indicar `auto_increment`.
No soluciones este error calculando `MAX(id)+1` desde PHP: generaria conflictos
entre solicitudes simultaneas.

Despues de exportar un respaldo completo, importa
[`SQL/reparar_autoincrement_infinityfree.sql`](SQL/reparar_autoincrement_infinityfree.sql)
en la base seleccionada. Este archivo corresponde a los tipos `INT` del esquema
de este proyecto. Conserva los registros y sus identificadores, incluido el ID
`0` que una importacion defectuosa haya dejado referenciado; no borra ni reinicia
tablas. La consulta final permite verificar las 13 claves automaticas.

Prueba local aislada (usa tablas temporales en la conexion):

```powershell
C:\xampp\php\php.exe database\tests\autoincrement_test.php
C:\xampp\php\php.exe database\tests\integridad_productos_pagos_test.php
```

## 8. Fotografias del catalogo

Las 41 fotografias (40 productos del catalogo y la hamburguesa existente) tienen
autor, fuente, licencia y huella SHA-256 en
[`MVCsix1.0/img/catalogo/fuentes.json`](MVCsix1.0/img/catalogo/fuentes.json).
Los creditos publicos estan en
[`MVCsix1.0/img/catalogo/creditos.html`](MVCsix1.0/img/catalogo/creditos.html)
y se enlazan desde el menu del cliente y la consulta de productos.
Son fotos ilustrativas: la presentacion y el envase no representan una fotografia
del plato realmente servido. Conserva las atribuciones y licencias al distribuirlas.

Una imagen subida por el administrador tiene prioridad sobre la foto del
catalogo. Los archivos se versionan en la URL para evitar mostrar fotos antiguas
desde la cache. Verifica las imagenes despues de subirlas, no solo el mensaje de
exito del Administrador de archivos:

```powershell
C:\xampp\php\php.exe database\tests\product_images_test.php
C:\xampp\php\php.exe database\validate_catalogo_40.php
```
