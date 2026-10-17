# Despliegue en Railway (Docker + MySQL)

## ¿Por qué Railway y no un hosting PHP tradicional?

| | Railway (Docker + MySQL) | Hosting PHP compartido (InfinityFree, etc.) |
|---|---|---|
| Triggers/procedimientos MySQL | Sí | Normalmente no (por eso existe `APP_DB_STOCK_TRIGGERS=0`) |
| Archivos subidos persistentes | Sí, con un Volume | Sí |
| Control de PHP/extensiones | Total (Dockerfile) | Limitado |
| HTTPS | Automático | Depende |
| Costo | De pago por uso (sin plan gratuito permanente) | Gratuito/barato |

Railway es adecuado: la app es PHP 7.4+/8.x clásico con `mysqli`, sin Composer. El hosting tradicional sigue siendo válido y más barato para pruebas (ver `DEPLOY_INFINITYFREE.md`).

## Qué se preparó

- `Dockerfile`: `php:8.2-apache` + `mysqli` (`mbstring`, `fileinfo`, `json`, `session` ya vienen en la imagen).
- `docker/entrypoint.sh`: configura Apache para escuchar en `$PORT` de Railway y restaura las imágenes de ejemplo en el volumen.
- `docker/apache.conf`: habilita `.htaccess` y oculta la versión del servidor.
- `.dockerignore`: deja fuera `.env`, `.git`, `SQL/`, `database/`, `desktop/`, logs y `*.md`.
- `railway.json`: usa el Dockerfile y reintenta si el contenedor falla.
- `docker-compose.yml`: solo para probar la imagen en local; los scripts SQL se montan como inicialización de MySQL y solo corren con un volumen vacío.

## Pasos

1. **Proyecto**: en Railway, *New Project* → *Deploy from GitHub repo* → elige este repositorio. Detectará el `Dockerfile`.
2. **Base de datos**: en el mismo proyecto, *New* → *Database* → *MySQL*.
3. **Variables del servicio web** (pestaña *Variables*). Usa referencias al servicio MySQL (el nombre `MySQL` puede variar):

   | Variable | Valor |
   |---|---|
   | `APP_ENV` | `production` |
   | `MYSQLHOST` | `${{MySQL.MYSQLHOST}}` |
   | `MYSQLPORT` | `${{MySQL.MYSQLPORT}}` |
   | `MYSQLUSER` | `${{MySQL.MYSQLUSER}}` |
   | `MYSQLPASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
   | `MYSQLDATABASE` | `${{MySQL.MYSQLDATABASE}}` |
   | `APP_DB_STOCK_TRIGGERS` | `0` (el esquema de `install_infinityfree.sql` no crea triggers) |
   | `APP_BASE_PATH` | vacío |
   | `APP_TIMEZONE` | `America/Montevideo` |

   Opcionales: `APP_DB_CHARSET`, `NOTIF_EMAIL_ENABLED`, `NOTIF_EMAIL_FROM`, `NOTIF_SMS_ENABLED`, `NOTIF_SMS_PROVIDER`, `NOTIF_SMS_API_KEY`. `APP_DB_*` tiene prioridad sobre `MYSQL*` si defines ambas. **No definas `PORT`**: Railway lo inyecta.
4. **Volume para imágenes**: en el servicio web, *Settings* → *Volumes* → *Add Volume* con *Mount path* `/var/www/html/MVCsix1.0/files`. Sin él, las imágenes subidas se pierden en cada redeploy.
5. **Dominio**: *Settings* → *Networking* → *Generate Domain*.
6. **Esquema (una sola vez, sin datos reales)**: desde tu PC, con el cliente `mysql` y los datos de conexión pública de Railway (pestaña *Connect* del servicio MySQL), importa en este orden `SQL/install_infinityfree.sql` y `SQL/catalogo_infinityfree.sql`. No uses `SQL/install.sql` (hace `DROP DATABASE` y crea otra base distinta de la de Railway).
7. **Administrador**: registra una cuenta en `/registro_cliente.php` y promuévela con el `UPDATE` indicado en `DEPLOY_INFINITYFREE.md`, sección 4.

## Notas

- Los logs de seguridad (`storage/logs`) y las sesiones viven en el contenedor y se pierden al redeployar; los errores de PHP salen por los logs de Railway.
- Si quieres usar triggers (`install.sql`), crea antes la base y ajusta el nombre; luego pon `APP_DB_STOCK_TRIGGERS=1`.
- Prueba local de la imagen (requiere Docker): `docker compose up --build` inicializa automáticamente el esquema y catálogo si `dbdata` está vacío; abre `http://localhost:8080`. Docker Compose conserva volúmenes entre ejecuciones: no borres un volumen con datos para volver a inicializarlo.
