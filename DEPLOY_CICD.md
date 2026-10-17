# Despliegue continuo (GitHub + Railway)

## Decisión
- **Despliegue (CD):** integración nativa de Railway con GitHub. No hace falta GitHub Actions ni tokens de Railway en el repositorio, así que no hay secretos en YAML.
- **Validación (CI):** `.github/workflows/ci.yml` (GitHub Actions) ejecuta, sin credenciales reales:
  - `php -l` en PHP 8.0 y 8.2
  - bloqueo de archivos sensibles versionados (`.env`, respaldos, logs)
  - importación de `SQL/install_infinityfree.sql` + `catalogo_infinityfree.sql` en un MySQL 8 efímero del runner
  - `docker build` (sin publicar)
- No hay pruebas unitarias en el proyecto, por lo que no se ejecutan.

## Rama de producción
Rama acordada: **`main`** (ya es la predeterminada; no se cambió). La integración nativa de Railway debe apuntar exclusivamente a `main`. CI corre en cualquier push y pull request para validar ramas de trabajo sin desplegarlas.

## Pasos manuales (una sola vez, requieren tu cuenta)
1. **GitHub → Settings → Branches → Add rule** para `main`:
   - Require a pull request before merging
   - Require status checks de `php-syntax` para PHP 8.0 y PHP 8.2, además de `no-secrets`, `schema` y `docker-build`
   - (opcional) Require approvals
2. **Railway → servicio web → Settings → Source**:
   - Branch: `main`
   - Activa **Wait for CI**: Railway espera a que pasen los checks antes de desplegar.
3. El workflow se ejecutará al hacer push o abrir un PR; los checks aparecerán en la lista después de la primera ejecución.

## Consecuencias
- Con esto, cada merge a `main` publica en producción automáticamente. Sin la regla de protección, cualquier push directo también lo haría.
- Sin "Wait for CI", Railway despliega aunque CI falle.
- Las **migraciones NO son automáticas**: el contenedor no ejecuta SQL. Los cambios de esquema se aplican a mano, con respaldo previo, según [DEPLOY_MIGRACION_BD.md](DEPLOY_MIGRACION_BD.md). No uses `install.sql` ni `BD.sql` (contienen `DROP DATABASE`). Los cambios de esquema deben ser aditivos (`ADD COLUMN`, `CREATE TABLE IF NOT EXISTS`) y revisados en el PR.
  Orden seguro: respaldo → aplicar migración aditiva → merge del código que la usa.

## Probar antes de publicar
1. Crea una rama (`git checkout -b mi-cambio`) y abre un PR hacia `main`.
2. Espera los checks verdes de CI.
3. Local: prueba en XAMPP (`http://127.0.0.1/proj/`) o, si tienes Docker, `docker compose up --build`.
4. Opcional: en Railway activa **PR Environments** (Settings → Environments) para tener una URL de prueba con su propia base. Nunca apuntes un entorno de prueba a la base de producción.
5. Haz merge solo con CI verde.

## Revertir un despliegue fallido
- **Rápido (Railway):** servicio web → *Deployments* → despliegue anterior bueno → *Redeploy* (o *Rollback*). Es inmediato y no toca la base de datos.
- **Permanente (Git):** `git revert <commit>` en una rama, PR y merge a `main`. No uses `push --force`.
- **Si hubo migración de BD:** el rollback de código no la deshace. Revierte con el respaldo previo siguiendo la sección de recuperación de [DEPLOY_MIGRACION_BD.md](DEPLOY_MIGRACION_BD.md) y solo con tu autorización, porque restaurar puede perder datos posteriores al respaldo.
- Los datos (MySQL y volumen de imágenes) persisten entre despliegues.
