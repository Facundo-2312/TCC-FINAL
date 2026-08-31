# SECURITY.md — RESTAURANTE-UY

Este documento resume la auditoría de seguridad aplicada y las medidas implementadas.
Ninguna funcionalidad existente fue eliminada; los cambios corrigen vulnerabilidades o
agregan controles nuevos de forma no destructiva.

## Resumen de medidas implementadas

### 1. CSRF (Cross-Site Request Forgery)
- Nueva clase `App\Support\Csrf` (`src/Support/Csrf.php`): token sincronizado por sesión,
  comparación con `hash_equals` (constant-time).
- Helpers globales en `app_bootstrap.php`: `csrf_field()` (imprime el input oculto) y
  `csrf_verify_or_die($redirectTo)` (verifica `$_POST['_csrf']`, registra el intento fallido
  en el log de seguridad y redirige/403 si no es válido).
- Aplicado a todos los formularios de acciones de estado que ya eran auto-contenidos
  (renderizan y procesan en el mismo archivo): `Login.php`/`interfazlog.php`,
  `mesas.php`, `CAJA.php`, `Cocina2.php`, `InterfazObtenerPedidos.php`,
  `crear.php`/`Actualizar.php` → `InterfazE.php` (alta/edición de empleados),
  `MVCsix1.0/create.php`/`update.php` → `InterfazProducto.php` (alta/edición de productos)
  y el enlace de borrado `MVCsix1.0/delete.php` (token como parámetro `_csrf`).

### 2. SQL Injection
- Corregido en la sesión anterior: `CambiarEstado.php`, `EliminarPedido.php`,
  `InterfazActualizarPedido.php` migrados a sentencias preparadas vía
  `App\Repositories\PedidoRepository`. Verificado que el resto de endpoints activos
  (mesas, caja, empleados, chatbot) ya usaban `mysqli_prepare`/bind.

### 3. XSS (almacenado/reflejado)
- Las vistas auditadas ya centralizan el escape de salida con una función `h()`
  (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) en `mesas.php`, `CAJA.php`, `Cocina2.php`,
  `InterfazObtenerPedidos.php`, `chatbot_api.php`. No se detectó `echo` directo de datos de
  usuario/BD sin escapar en esos flujos.

### 4. Autenticación insegura / gestión de contraseñas
- `Empleado::create()` y `Empleado::update()` ahora exigen contraseña mínima de 8
  caracteres (antes no había ninguna validación de fortaleza).
- Las contraseñas se guardan con `password_hash()` (bcrypt); el login sigue migrando
  automáticamente hashes MD5/planos heredados al formato seguro en el primer acceso
  exitoso (comportamiento ya existente, verificado y conservado).

### 5. Rate limiting / fuerza bruta en login
- Nueva clase `App\Support\LoginThrottle` (`src/Support/LoginThrottle.php`): bloquea
  usuario+IP durante 15 minutos tras 5 intentos fallidos. Persiste en una tabla
  `intentos_login` creada automáticamente (`CREATE TABLE IF NOT EXISTS`, no destructivo).
  Integrado en `interfazlog.php`: revisa el bloqueo antes de intentar el login, registra
  cada fallo y limpia el contador tras un login exitoso.

### 6. Gestión de sesiones / cookies
- Ya existente y verificado: `httponly`, `secure` (cuando hay HTTPS), `samesite=Lax`,
  expiración por inactividad (2h), regeneración periódica de ID de sesión (cada 15 min)
  y regeneración inmediata tras login exitoso (previene *session fixation*).
- **Nuevo**: mitigación de *session hijacking* — `App\Support\Auth::guardAgainstHijacking()`
  vincula la sesión autenticada a un hash del `User-Agent`; si una cookie de sesión robada
  se reutiliza desde un cliente distinto, la sesión se destruye automáticamente y se
  registra el evento en el log de seguridad.

### 7. Exposición de información sensible / errores visibles al usuario
- `App\Support\ErrorHandler` ahora desactiva `display_errors` en producción
  (`APP_ENV=production`, valor por defecto) y centraliza el manejo de excepciones no
  capturadas con un mensaje genérico (el detalle real se escribe en el log de errores de
  PHP, no en la respuesta HTTP).
- Nueva clase `App\Support\Db::fail()`: reemplaza el patrón
  `die("Error ..." . mysqli_error($con))` que filtraba nombres de tablas/columnas y
  fragmentos de SQL al navegador. Aplicado en `Empleado.php` (conexión, login, listado),
  `mesas.php`, `CAJA.php`, `Cocina2.php`, `InterfazObtenerPedidos.php`: ahora se muestra un
  mensaje genérico y el detalle técnico se registra en `storage/logs/security.log`.

### 8. Credenciales en código / variables de entorno
- Nueva clase `App\Support\Env` (`src/Support/Env.php`): carga un archivo `.env` real
  (antes solo se leían variables de entorno del sistema operativo, `.env` no existía ni
  se procesaba). Se agregó `.env.example` con todas las claves soportadas
  (`APP_ENV`, `APP_BASE_PATH`, `APP_DB_*`, `APP_SECURITY_LOG_PATH`) — **sin credenciales
  reales**. `.gitignore` actualizado para excluir `.env` y los logs en `storage/logs/`.

### 9. Logs de seguridad
- Nueva clase `App\Support\SecurityLog` (`src/Support/SecurityLog.php`): registra en
  `storage/logs/security.log` (ruta configurable vía `APP_SECURITY_LOG_PATH`) los eventos:
  `login_exitoso`, `login_fallido`, `login_bloqueado`, `csrf_rechazado`,
  `sesion_secuestro_sospechoso`, `error_bd`.

### 10. Subida de archivos
- `MVCsix1.0/InterfazProducto.php::guardarImagenProducto()` ya validaba extensión
  permitida, tamaño máximo (2 MB) y generaba un nombre de archivo aleatorio (`uniqid`,
  evita *path traversal*/sobrescritura). Se agregó una verificación adicional del **tipo
  MIME real** del archivo (`finfo_file`) para rechazar archivos disfrazados con una
  extensión de imagen falsa.

### 11. Open Redirect / Path Traversal / Command Injection
- Revisados todos los `header('Location: ...')` / `app_redirect()` del proyecto: ningún
  destino de redirección proviene de entrada de usuario (`$_GET`/`$_POST`); todos son
  rutas fijas en código. No se requirió corrección.
- No se encontró uso de `include`/`require` con rutas controladas por el usuario, ni de
  `exec`/`shell_exec`/`system`/`eval`. No se requirió corrección.

## Alcance y elementos pendientes (documentados, no corregidos en esta pasada)

Por transparencia, quedan fuera del alcance de esta auditoría (deuda técnica ya señalada
en el análisis arquitectónico previo, no vulnerabilidades nuevas introducidas):

- `CambiarEstado.php` y `EliminarPedido.php` (ya libres de inyección SQL) y
  `InterfazActualizarPedido.php` son endpoints **huérfanos**: ningún archivo del proyecto
  los enlaza actualmente (superados por los formularios CSRF-protegidos de
  `Cocina2.php`). Se recomienda eliminarlos en una limpieza futura en vez de protegerlos.
- Los mensajes `die("Error ... " . mysqli_error(...))` dentro de las clases legacy
  `MVCsix1.0/Producto.php`, `MVCsix1.0/Pepc.php` y `MVCsix1.0/Pedido.php` no fueron
  migrados a `App\Support\Db::fail()` en esta pasada (alto número de ocurrencias
  repetidas); quedan como seguimiento recomendado.
- No se implementó 2FA, expiración/rotación forzada de contraseñas, ni panel de
  administración de sesiones activas — quedan para una fase posterior si se requieren.

## Variables de entorno

Copiar `.env.example` a `.env` y completar con credenciales reales del entorno
(nunca commitear `.env`). Ver `.env.example` para el listado completo de claves.
