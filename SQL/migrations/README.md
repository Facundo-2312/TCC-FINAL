# Migraciones SQL — RESTAURANTE-UY

Carpeta de migraciones **idempotentes** (se pueden ejecutar más de una vez sin error)
para bases de datos ya instaladas con `SQL/BD.sql`. Usan sintaxis nativa de MariaDB
(`ADD COLUMN IF NOT EXISTS`, `ADD INDEX IF NOT EXISTS`, `DROP FOREIGN KEY IF EXISTS`,
etc. — verificado contra MariaDB 10.4.32) en vez de `DROP TABLE`/reescrituras
destructivas. Ninguna migración elimina tablas, columnas ni datos existentes.

## Cómo aplicarlas

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

## Notas de compatibilidad

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
