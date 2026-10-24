# TCC-FINAL — preparación segura para hosting gratuito

## Estado de esta rama
Esta rama **NO despliega** la aplicación. Agrega verificaciones y documenta decisiones pendientes; no toca `main`, cuentas de hosting ni datos de producción.

## Hallazgos del repositorio
- Aplicación PHP 8.2 con `mysqli`, Apache y sesiones; GitHub Pages **no ejecuta PHP ni MySQL**.
- Hay `Dockerfile`, `docker-compose.yml`, `.htaccess`, `app_bootstrap.php` y variables de entorno documentadas.
- `.github/workflows/ci.yml` ya verifica sintaxis PHP, archivos sensibles, esquema MySQL 8 y compilación Docker. No realiza CD.
- `SQL/install_infinityfree.sql` y `SQL/catalogo_infinityfree.sql` son el punto de partida documentado para **una base vacía de pruebas**.
- La documentación existente advierte que `SQL/install.sql` y `SQL/BD.sql` pueden borrar bases y generar usuarios demo: **no usarlos en producción**.
- `MVCsix1.0/files` contiene archivos subidos que deben persistir fuera de cualquier sincronización destructiva.
- El repositorio contiene un archivo `proyectomagnus_rescate_mysql.sql`: revisar su contenido y datos antes de cualquier distribución adicional; **no publicarlo en el servidor web**.

## Hosting y costo
Objetivo: gratuito, sin tarjeta, URL HTTPS pública, PHP 8.2 compatible, MySQL/MariaDB, FTP/SFTP o contenedor, acceso 24/7 razonable.
**No se ha verificado aún un proveedor que garantice simultáneamente todos esos requisitos.**
No seleccionar ni activar servicios de pago automáticamente.

## Lista de comprobación antes de activar CD
1. Confirmar alta real en un hosting gratuito y sus términos de uso.
2. Confirmar PHP 8.2, `mysqli`, `.htaccess`, HTTPS, MySQL y límites de conexiones.
3. Confirmar acceso FTP seguro/FTPS/SFTP desde GitHub Actions (algunos hostings gratuitos lo restringen).
4. Definir directorio web, punto de entrada real (no existe `index.php` en la raíz), y redirección de inicio si hace falta.
5. Mantener `.env`, credenciales y logs fuera del directorio público siempre que el proveedor lo permita; bloquear acceso HTTP a rutas privadas.
6. Configurar secretos del hosting en GitHub Actions; nunca imprimir contraseñas en logs.
7. Usar una base **nueva y vacía** para importar esquema y catálogo. No reimportar el esquema en cada despliegue.
8. Separar archivos persistentes, en especial `MVCsix1.0/files`, de los archivos versionados. No utilizar `rsync --delete` o borrado remoto sin revisión.
9. Probar autenticación, sesiones, cliente, mozo, cocina, caja, pagos, imágenes, reservas y errores sin exponer secretos.
10. Activar despliegue tras CI exitoso y preferiblemente mediante entorno GitHub `production` con aprobación.
11. Registrar URL, procedimiento de rollback y respaldo de MySQL.

## Decisión
Hasta elegir un proveedor real y validar acceso seguro, no crear un workflow que intente subir archivos a un destino inventado. El próximo cambio será un PR separado, específico del proveedor, con CD y exclusiones auditadas.
