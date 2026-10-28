-- Coordenadas opcionales seleccionadas por el administrador en OpenStreetMap.
ALTER TABLE empleado
    ADD COLUMN IF NOT EXISTS direccion_latitud DECIMAL(10,7) NULL AFTER Direccion,
    ADD COLUMN IF NOT EXISTS direccion_longitud DECIMAL(10,7) NULL AFTER direccion_latitud;
