function initializeMap(elementId, address) {
    const geocoder = new google.maps.Geocoder();

    // Find the map element by its unique ID
    const mapElement = document.getElementById(elementId);
    if (!mapElement) return;

    // Create the map instance
    const map = new google.maps.Map(mapElement, {
        zoom: 14,
        center: { lat: 0, lng: 0 }, // Default center
        mapTypeId: google.maps.MapTypeId.ROADMAP,
    });

    // Use the Geocoding API to convert the address into geographic coordinates
    geocoder.geocode({ address: address }, function (results, status) {
        if (status === "OK" && results.length > 0) {
            const location = results[0].geometry.location;
            map.setCenter(location);

            // Place a marker at the address location
            new google.maps.Marker({
                position: location,
                map: map,
                title: address,
            });
        } else {
            console.error(`Geocode failed for address "${address}" with status: ${status}`);
        }
    });
}
