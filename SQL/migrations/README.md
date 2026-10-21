# Migraciones SQL — RESTAURANTE-UY

Carpeta de migraciones **idempotentes** (se pueden ejecutar más de una vez sin error)
para bases de datos ya instaladas con `SQL/BD.sql`. Usan sintaxis nativa de MariaDB
(`ADD COLUMN IF NOT EXISTS`, `ADD INDEX IF NOT EXISTS`, `DROP FOREIGN KEY IF EXISTS`,
etc. — verificado contra MariaDB 10.4.32) en vez de `DROP TABLE`/reescrituras
destructivas. Ninguna migración elimina tablas, columnas ni datos existentes.

## Cómo aplicarlas

### Método recomendado: runner versionado

El runner registra cada archivo aplicado en la tabla `schema_migrations`; por eso
puede ejecutarse repetidamente sin volver a aplicar versiones ya registradas:

```powershell
C:\xampp\php\php.exe database\migrate.php --status
C:\xampp\php\php.exe database\migrate.php --dry-run
C:\xampp\php\php.exe database\migrate.php
```

El archivo `SQL/install.sql` sigue siendo exclusivamente para una instalación
nueva y limpia. Para actualizar una base de datos existente, usar este runner.

### Método manual

Ejecutar en orden (0 primero) contra una base de datos existente:

```powershell
Get-ChildItem SQL\migrations\*.sql | Sort-Object Name | ForEach-Object {
    C:\xampp\mysql\bin\mysql.exe -u root ProyectoMagnus -e "source $($_.FullName)"
}
```

O una por una con phpMyAdmin / tu cliente SQL preferido, en el orden numérico del
nombre de archivo.

Para una instalación **nueva** (base de datos limpia), no hace falta aplicar estas
migraciones: usar directamente `SQL/install.sql`, que ya incluye todas estas mejoras.

## Contenido

| Archivo | Qué corrige/agrega |
|---|---|
| `000_baseline_propina.sql` | Reexpresa `SQL/propina.sql` de forma idempotente. |
| `001_indices.sql` | Índices en `pedidos.estado/fecha`, `productos.estado`, `pagos.fecha`; UNIQUE en `mesas.numero` y `roles.nombre`. |
| `002_timestamps.sql` | `creado_en`/`actualizado_en` en productos, mesas, usuarios, empleado, pedidos. |
| `003_estado_pedidos.sql` | Corrige bug real: agrega `ArchivadoCocina` al ENUM de `pedidos.estado` (usado por Cocina2.php/CAJA.php pero no declarado). |
| `004_integridad_empleados.sql` | FK real `empleado.id_rol -> roles`, sincronizada automáticamente vía trigger a partir del texto `Rol`; agrega `empleado.estado`. |
| `005_integridad_mesas_historial.sql` | FK `mesas_historial.id_mesa -> mesas`. |
| `006_integridad_stock.sql` | Corrige 2 bugs reales: sobreventa (sin validar stock disponible) y pérdida permanente de stock al cancelar/eliminar un pedido. |
| `007_integridad_pagos_caja.sql` | UNIQUE en `pagos.id_pedido` (evita pagos duplicados por condición de carrera) y regla "solo una caja abierta a la vez". |
| `008_optimizacion_pagos.sql` | Columna generada `pagos.fecha_dia` + índice, para que los reportes diarios no dependan de `DATE(fecha)=CURDATE()` (no usa índice). |
| `009_intentos_login.sql` | Formaliza la tabla de rate-limiting de login (ya se creaba en runtime desde PHP). |
| `010_pedido_mozo_personalizacion.sql` | Añade observaciones por pedido y personalizaciones de ingredientes por línea, preservando el flujo de mozo legacy en el esquema central. |
| `014_solicitudes_cobro.sql` | Guarda solicitudes de cobro del cliente sin registrar pagos; Caja confirma el dinero recibido. Incluida en los esquemas de instalacion nuevos. |

## Solicitar el cobro desde el cliente

En **Mi cuenta / Pagar**, el cliente ve sus ultimos 10 pedidos con detalle y total.
Puede solicitar o actualizar el medio de pago de un pedido propio entregado y no
pagado (Efectivo, Tarjeta o Transferencia). Una solicitud no mueve dinero, no
registra un pago y no libera la mesa. Caja ve la solicitud automaticamente cada
5 segundos y confirma el dinero recibido mediante el circuito de facturacion
existente. Caja muestra el monto a cobrar calculado automaticamente desde el
total del pedido, no editable y sin propina adicional en este formulario.
El servidor obtiene el importe de la base de datos, no del navegador.
El panel no muestra propinas; conserva los registros historicos sin modificarlos.
El resumen incluye cobrado hoy, total de pedidos entregados pendientes de cobro,
solicitudes de clientes pendientes y cantidad de pagos de hoy. Las solicitudes
aparecen primero y se muestra el historial de los ultimos 10 pagos de hoy.
Resumen, tarjetas de pedidos e historial se actualizan cada 5 segundos sin
recargar formularios; se preserva el medio elegido manualmente por el cajero.
El cliente ve la confirmacion automaticamente.
No se solicitan datos de tarjeta ni comprobantes bancarios en esta modalidad.

Validacion aislada (usa tablas temporales, sin cambiar pedidos ni pagos reales):
`C:\xampp\php\php.exe database\tests\solicitudes_cobro_test.php`.

## Panel de Cocina

El panel organiza los pedidos por estado, con los mas antiguos primero, numero
de mesa, tiempo desde la creacion, notas y personalizaciones de ingredientes.
Las tarjetas y contadores se actualizan cada 5 segundos sin recargar.
El mozo conserva acceso solo de lectura; administrador y cocina pueden usar
botones explicitos para preparar, entregar, volver a pendientes o retirar.
Retirar archiva el pedido: no lo elimina ni confirma pagos.
Las acciones verifican CSRF y el estado anterior para evitar cambios repetidos
o sobrescribir una actualizacion de otra pantalla. No se cambia el estado al
tocar accidentalmente una tarjeta.

Pruebas aisladas: `C:\xampp\php\php.exe database\tests\cocina_test.php`.

## Notas de compatibilidad

Las fotos conocidas del catalogo se resuelven en `src/Support/ProductImage.php`
para cliente, consulta de cocina/mozo y administracion. Esto evita reutilizar
fotos antiguas cuyo contenido no corresponde al producto. No requiere migracion.
Las URLs incluyen la fecha de modificacion para invalidar la cache.
Las cuatro fotos base son archivos locales versionados en Git, no imagenes
aleatorias ni servicios externos. La prueba verifica sus huellas SHA-256 para
detectar reemplazos accidentales; cambiar la ruta antigua en la BD no las sustituye.
Prueba: `C:\xampp\php\php.exe database\tests\product_images_test.php`.
Con la base local disponible, agregar `consulta`, `cliente` o `admin` verifica
tambien las imagenes versionadas en el HTML de cada pantalla.

- Todas las columnas nuevas son aditivas (con `DEFAULT`), no cambian el comportamiento
  de `SELECT *` usado en varias vistas (PHP accede por nombre de columna, no por índice
  posicional).
- Los `CHECK` constraints (`006_integridad_stock.sql`) requieren MariaDB >= 10.2.1 /
  MySQL >= 8.0.16. Verificado en este proyecto: MariaDB 10.4.32.
- `007_integridad_pagos_caja.sql` fallará con un error explícito si ya existieran pagos
  duplicados por pedido en tus datos actuales (no debería ocurrir: la app ya lo evita a
  nivel de aplicación). Revisa con
  `SELECT id_pedido, COUNT(*) FROM pagos GROUP BY id_pedido HAVING COUNT(*) > 1;`
  antes de aplicarla si tienes dudas.
