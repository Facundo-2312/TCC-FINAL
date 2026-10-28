(function () {
    var mapElement = document.getElementById('employee-map');
    var latitudeInput = document.getElementById('direccion_latitud');
    var longitudeInput = document.getElementById('direccion_longitud');
    var status = document.getElementById('employee-map-status');
    var clearButton = document.getElementById('employee-map-clear');

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
    var start = hasInitialPoint ? [initialLatitude, initialLongitude] : [-34.9011, -56.1645];
    var map = L.map(mapElement).setView(start, hasInitialPoint ? 16 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
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
