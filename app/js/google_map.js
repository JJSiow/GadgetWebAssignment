var geocoder, map, marker;

function initMap() {
    geocoder = new google.maps.Geocoder();

    var lat_value = document.getElementById('address_latitude').value || 3.2018902;
    var long_value = document.getElementById('address_longitude').value || 101.717157;

    var latlng = new google.maps.LatLng(lat_value, long_value);

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 14,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    marker = new google.maps.Marker({
        position: latlng,
        map: map,
        draggable: true
    });

    geocodePosition(latlng);

    marker.addListener("dragend", function () {
        geocodePosition(marker.getPosition());
        updateMarkerPosition(marker.getPosition());
    });

    var address = document.getElementById("address_detail").value;
    if (address) {
        geocodeAddress(address);
    }

    document.getElementById("search").addEventListener("click", function () {
        event.preventDefault();
        geocodeAddress();
    });
}

function updateMarkerAddress(address) {
    // Remove Plus Code from address
    var cleanedAddress = address.replace(/^[^,]*\+[^\s,]*\s*,\s*/i, '').trim();
    document.getElementById("address_detail").value = cleanedAddress;
}

function updateMarkerPosition(latlng) {
    document.getElementById('address_latitude').value = latlng.lat();
    document.getElementById('address_longitude').value = latlng.lng();
}

function geocodePosition(pos) {
    geocoder.geocode({
        latLng: pos
    }, function (responses, status) {
        if (status === "OK" && responses.length > 0) {
            var fullAddress = responses[0].formatted_address;
            updateMarkerAddress(fullAddress);
            document.getElementById("output").textContent = "Address: " + fullAddress;
        } else {
            document.getElementById("output").textContent = "Geocode failed: " + status;
        }
    });
}

function geocodeAddress() {
    var address = document.getElementById("address_detail").value;
    geocoder.geocode({
        address: address
    }, function (results, status) {
        if (status === "OK") {
            var location = results[0].geometry.location;
            map.setCenter(location);
            marker.setPosition(location);
            updateMarkerPosition(location);
            updateMarkerAddress(results[0].formatted_address);
            document.getElementById("output").textContent = "Address: " + results[0].formatted_address;
        } else {
            document.getElementById("output").textContent = "Geocode failed: " + status;
        }
    });
}
window.onload = initMap;