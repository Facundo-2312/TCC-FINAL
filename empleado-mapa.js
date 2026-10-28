(function () {
    var config = window.OpenStreetMapConfig;
    if (!config) return;

    var mapElement = document.getElementById(config.mapElementId);
    var latitudeInput = document.getElementById(config.latitudeField);
    var longitudeInput = document.getElementById(config.longitudeField);
    var status = document.getElementById(config.statusElementId);
    var clearButton = document.getElementById(config.clearButtonId);

    if (!mapElement || !latitudeInput || !longitudeInput || !status || !clearButton) return;
    if (!window.L) {
        status.textContent = 'No se pudo cargar OpenStreetMap. La ubicacion es opcional; verifica tu conexion para usar el mapa.';
        clearButton.disabled = true;
        return;
    }

    var initialLatitude = Number(latitudeInput.value);
    var initialLongitude = Number(longitudeInput.value);
    var hasInitialPoint = latitudeInput.value !== '' && longitudeInput.value !== ''
        && Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);
    var start = hasInitialPoint ? [initialLatitude, initialLongitude] : config.defaultCenter;
    var map = L.map(mapElement).setView(start, hasInitialPoint ? config.selectedZoom : config.defaultZoom);
    L.tileLayer(config.tileUrlTemplate, {
        maxZoom: config.maxZoom,
        attribution: config.attribution
    }).addTo(map);

    var marker = null;

    function savePoint(point) {
        var latitude = Number(point.lat).toFixed(7);
        var longitude = Number(point.lng).toFixed(7);
        latitudeInput.value = latitude;
        longitudeInput.value = longitude;

        if (marker) {
            marker.setLatLng(point);
        } else {
            marker = L.marker(point, {draggable: true}).addTo(map);
            marker.on('dragend', function (event) {
                savePoint(event.target.getLatLng());
            });
        }

        status.textContent = 'Ubicacion seleccionada: ' + latitude + ', ' + longitude + '.';
        clearButton.disabled = false;
    }

    if (hasInitialPoint) {
        marker = L.marker(start, {draggable: true}).addTo(map);
        marker.on('dragend', function (event) {
            savePoint(event.target.getLatLng());
        });
        status.textContent = 'Ubicacion guardada: ' + initialLatitude.toFixed(7) + ', ' + initialLongitude.toFixed(7) + '.';
    } else {
        status.textContent = 'Haz clic en el mapa para seleccionar la ubicacion.';
    }

    map.on('click', function (event) {
        savePoint(event.latlng);
    });

    clearButton.addEventListener('click', function () {
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        latitudeInput.value = '';
        longitudeInput.value = '';
        status.textContent = 'No se guardara ninguna ubicacion para esta direccion.';
    });
}());
