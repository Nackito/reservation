function init(lat, lng, label = "Résidence") {
    // Si lat/lng non fournis, centrer sur la Côte d'Ivoire
    const centerLat = lat ?? 7.54;
    const centerLng = lng ?? -5.55;
    const zoom = lat && lng ? 15 : 7;
    const map = L.map("map").setView([centerLat, centerLng], zoom);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution:
            '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    if (
        typeof lat === "number" &&
        typeof lng === "number" &&
        !isNaN(lat) &&
        !isNaN(lng)
    ) {
        L.marker([lat, lng]).addTo(map).bindPopup(label);
    }
}

// Rendre la fonction accessible globalement pour les scripts inline (Vite)
window.init = init;

//init();
