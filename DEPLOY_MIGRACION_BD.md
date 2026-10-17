# Migración de base de datos a producción (Railway MySQL)

**Decisión tomada:** base de producción **vacía** (esquema + catálogo de 40 productos). No se migran pedidos, pagos, usuarios ni productos locales. Stock gestionado por PHP (`APP_DB_STOCK_TRIGGERS=0`, sin triggers). Zona horaria `America/Montevideo`.

## Análisis

- **Esquema de producción:** `SQL/install_infinityfree.sql` (14 tablas, sin `DROP`, sin `CREATE DATABASE`, sin triggers/vistas/procedimientos, sin `DEFINER`) y luego `SQL/catalogo_infinityfree.sql` (idempotente: `INSERT ... WHERE NOT EXISTS`).
- **Validado localmente** en una base temporal (ya eliminada): importa sin errores; 14 tablas, 5 roles, 6 mesas, 40 productos, 0 usuarios; ya incluye lo de las migraciones 001–012 (`reservas`, `intentos_login`, rol Cliente, `fecha_dia`, `observaciones`, `sin/extra_ingredientes`, estado `ArchivadoCocina`, índices únicos de caja/pagos/mesas, CHECK).
- **No usar** `install.sql` ni `BD.sql` (hacen `DROP DATABASE`/crean otra base, y crean `admin/admin123`), ni las migraciones (solo para bases antiguas).
- **Dependencias (FK):** `roles` → `usuarios` → `reservas`, `caja`, `pedidos`; `categorias` → `productos`; `mesas` → `mesas_historial`, `reservas`; `pedidos` → `detalle_pedido`, `pagos`; `caja` → `movimientos_caja`. Importar siempre el archivo completo, en ese orden.
- **Respaldo local existente** (`storage/logs/backup_predefensa_*.sql`): esquema antiguo con 4 usuarios demo (MD5 triviales). **No se importa** y no debe subirse ni versionarse (está en `.gitignore` y `.dockerignore`).
- **Charset/zona:** BD `utf8mb4`; la conexión usa `utf8mb4` y la app fija en cada conexión `SET time_zone` al desfase de `APP_TIMEZONE` (comprobado: hora PHP = hora MySQL). Railway usa UTC por defecto en el servidor, por eso se fija desde la app.
- **Permisos:** la app solo ejecuta `SELECT/INSERT/UPDATE/DELETE` y crea `intentos_login` si no existe (`CREATE TABLE IF NOT EXISTS`, ya incluida en el esquema). Railway entrega un usuario administrador del servicio; si quieres mínimo privilegio, ver el paso 5.

## 1. Respaldo (antes de tocar nada)

Respaldo de tu base local, **solo como copia de seguridad privada** (no se importará):

```powershell
mkdir $HOME\backups -Force
C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --routines --triggers --default-character-set=utf8mb4 --result-file="$HOME\backups\proyectomagnus_$(Get-Date -f yyyyMMdd_HHmm).sql" proyectomagnus
```

Guárdalo fuera del repositorio. Si la base de Railway ya tuviera datos, respáldala igual antes del paso 3 (ver «Recuperación»).

## 2. Preparar variables

En el servicio web de Railway (ver `DEPLOY_RAILWAY.md`): `MYSQL*` por referencia, `APP_ENV=production`, `APP_DB_STOCK_TRIGGERS=0`, `APP_TIMEZONE=America/Montevideo`. Nada de esto va al repositorio.

## 3. Importación (solo sobre una base nueva y vacía)

Obtén host, puerto, usuario, contraseña y base de la pestaña *Connect* del servicio MySQL (conexión pública). Pásalos como variables de la sesión, sin escribirlos en archivos:

```powershell
$env:MYSQL_PWD = Read-Host "Contraseña MySQL de Railway"   # evita ponerla en el historial
$h = "HOST_PUBLICO"; $p = "PUERTO"; $u = "USUARIO"; $db = "NOMBRE_BASE"

# 3.0 Comprobar que está vacía (debe mostrar 0)
mysql -h $h -P $p -u $u $db -e "SELECT COUNT(*) AS tablas FROM information_schema.tables WHERE table_schema = DATABASE()"

# 3.1 Esquema, 3.2 catálogo (cmd /c por el operador <)
cmd /c "mysql -h $h -P $p -u $u --default-character-set=utf8mb4 $db < SQL\install_infinityfree.sql"
cmd /c "mysql -h $h -P $p -u $u --default-character-set=utf8mb4 $db < SQL\catalogo_infinityfree.sql"

# 3.3 Validación (solo lectura)
cmd /c "mysql -h $h -P $p -u $u $db < SQL\validar_migracion.sql"
Remove-Item Env:MYSQL_PWD
```

Si el paso 3.0 muestra tablas ≠ 0, **detente**: no importes encima.

## 4. Primer administrador

`/registro_cliente.php` → crear cuenta con contraseña fuerte y única → promover (sustituye el usuario):

```sql
UPDATE usuarios SET id_rol = (SELECT id_rol FROM roles WHERE nombre = 'Administrador' LIMIT 1)
WHERE usuario = 'TU_USUARIO_ADMIN';
```

No existen cuentas demo en producción. Crea empleados desde el panel de administración.

## 5. Mínimo privilegio (opcional, recomendado)

Conectado como administrador del servicio MySQL, crea un usuario solo para la app y úsalo en `APP_DB_USER`/`APP_DB_PASSWORD` del servicio web (en vez de `MYSQLUSER`):

```sql
CREATE USER 'app_restaurante'@'%' IDENTIFIED BY '<contraseña-generada-por-ti>';
GRANT SELECT, INSERT, UPDATE, DELETE ON NOMBRE_BASE.* TO 'app_restaurante'@'%';
```

Evita `GRANT ALL`. Si más adelante aplicas migraciones, hazlo con el usuario administrador, no con el de la app.

## 6. Pruebas funcionales

| Prueba | Esperado |
|---|---|
| `/Login.php` con el admin creado | Acceso al panel; hash migra a bcrypt |
| 5 intentos fallidos de login | Bloqueo 15 min (`intentos_login`) |
| `MVCsix1.0/index.php`: ver productos | 40 productos con imagen |
| Crear/editar producto con imagen | Se guarda en `MVCsix1.0/files` y sobrevive a un redeploy (volumen) |
| `mesas.php`: abrir mesa y crear pedido | Pedido y detalle guardados; **stock descontado** por PHP |
| Cancelar el pedido | Stock repuesto |
| `Cocina2.php`: cambiar estados | Pendiente → Preparando → Entregado |
| `CAJA.php`: abrir caja, cobrar con propina | Pago único por pedido; segunda caja abierta rechazada |
| `registro_cliente.php` + `cliente.php`: reservar mesa | Reserva guardada con hora local correcta (-03:00) |
| Caracteres `ñ á é` en nombre de cliente/producto | Se muestran bien (utf8mb4) |
| Forzar error (variables de BD erróneas en un entorno de prueba) | Mensaje genérico, sin credenciales; detalle solo en logs |

## 7. Recuperación en caso de error

- **Importación a medias o errores:** como la base de producción era nueva, bórrala y créala de nuevo desde el panel de Railway (o elimina las tablas creadas) y repite el paso 3. **Solo** si estás seguro de que no contiene datos reales.
- **Si la base ya tenía datos** y quieres volver atrás: antes del paso 3 exporta con `mysqldump -h $h -P $p -u $u --single-transaction --result-file=backup_prod_previo.sql $db` y restáuralo con `mysql ... $db < backup_prod_previo.sql` sobre una base vacía.
- **La app falla tras importar:** revisa variables (`MYSQL*`, `APP_DB_*`), logs de Railway y `validar_migracion.sql`. Tu base local no se ve afectada en ningún paso.
- **Datos locales:** permanecen en tu XAMPP y en el respaldo del paso 1.
