function init() {
    const map = L.map("map").setView([7.54, -5.55], 7);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
    }).addTo(map);
}

init();
