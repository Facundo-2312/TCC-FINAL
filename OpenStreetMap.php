<?php

final class OpenStreetMap
{
    private const LEAFLET_VERSION = '1.9.4';
    private const MAP_ELEMENT_ID = 'employee-map';
    private const LATITUDE_FIELD = 'direccion_latitud';
    private const LONGITUDE_FIELD = 'direccion_longitud';
    private const STATUS_ELEMENT_ID = 'employee-map-status';
    private const CLEAR_BUTTON_ID = 'employee-map-clear';

    private static function configuration()
    {
        return array(
            'mapElementId' => self::MAP_ELEMENT_ID,
            'latitudeField' => self::LATITUDE_FIELD,
            'longitudeField' => self::LONGITUDE_FIELD,
            'statusElementId' => self::STATUS_ELEMENT_ID,
            'clearButtonId' => self::CLEAR_BUTTON_ID,
            'tileUrlTemplate' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            'defaultCenter' => array(-34.9011, -56.1645),
            'defaultZoom' => 12,
            'selectedZoom' => 16,
            'maxZoom' => 19,
        );
    }

    private static function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function renderStylesheets()
    {
        $leafletCssIntegrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
        echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@' . self::LEAFLET_VERSION . '/dist/leaflet.css" integrity="' . $leafletCssIntegrity . '" crossorigin="anonymous">' . PHP_EOL;
        echo '<link rel="stylesheet" href="' . self::escape(app_url('estilos/empleado-ubicacion.css')) . '">' . PHP_EOL;
    }

    public static function renderEmployeeLocation($latitude = '', $longitude = '')
    {
        ?>
        <section class="employee-location" aria-labelledby="employee-location-title">
            <h3 id="employee-location-title">Ubicacion de la direccion (opcional)</h3>
            <p>Haz clic en el mapa para marcar el punto exacto. Puedes arrastrar el marcador para ajustarlo.</p>
            <div id="<?php echo self::MAP_ELEMENT_ID; ?>" role="application" aria-label="Mapa de OpenStreetMap para elegir la ubicacion"></div>
            <p id="<?php echo self::STATUS_ELEMENT_ID; ?>" role="status" aria-live="polite">Cargando mapa...</p>
            <input type="hidden" name="<?php echo self::LATITUDE_FIELD; ?>" id="<?php echo self::LATITUDE_FIELD; ?>" value="<?php echo self::escape($latitude); ?>">
            <input type="hidden" name="<?php echo self::LONGITUDE_FIELD; ?>" id="<?php echo self::LONGITUDE_FIELD; ?>" value="<?php echo self::escape($longitude); ?>">
            <button id="<?php echo self::CLEAR_BUTTON_ID; ?>" type="button">Quitar ubicacion seleccionada</button>
            <small>Mapa © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a></small>
        </section>
        <?php
    }

    public static function renderScripts()
    {
        $configuration = json_encode(
            self::configuration(),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
        $leafletIntegrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
        ?>
        <script>window.OpenStreetMapConfig = <?php echo $configuration; ?>;</script>
        <script defer src="https://unpkg.com/leaflet@<?php echo self::LEAFLET_VERSION; ?>/dist/leaflet.js" integrity="<?php echo $leafletIntegrity; ?>" crossorigin="anonymous"></script>
        <script defer src="<?php echo self::escape(app_url('empleado-mapa.js')); ?>"></script>
        <?php
    }

    public static function locationUrl($latitude, $longitude)
    {
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        return 'https://www.openstreetmap.org/?mlat=' . $latitude
            . '&mlon=' . $longitude
            . '#map=17/' . $latitude . '/' . $longitude;
    }
}
