// --- Flatpickr réservation ---
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { French } from "flatpickr/dist/l10n/fr.js";

function initFlatpickrReservation() {
    if (typeof flatpickr !== "function") return;
    const occupiedDates = window.occupiedDates || [];
    ["#ReservationDateRange", "#ReservationDateRange2"].forEach((selector) => {
        const el = document.querySelector(selector);
        if (el) {
            flatpickr(el, {
                mode: "range",
                dateFormat: "Y-m-d", // valeur envoyée à Livewire
                altInput: true,
                altFormat: "j F Y", // affichage utilisateur
                disable: occupiedDates,
                minDate: "today",
                locale: French,
            });
        }
    });
}

document.addEventListener("DOMContentLoaded", initFlatpickrReservation);

document.addEventListener("livewire:navigated", initFlatpickrReservation);
document.addEventListener("livewire:load", function () {
    if (window.livewire) {
        window.livewire.hook("message.processed", () => {
            initFlatpickrReservation();
        });
    }
});

import "./bootstrap";

import Alpine from "alpinejs";
import "preline";
import Swal from "sweetalert2";
import "./echo";
import "./leaflet"; // Importation de Leaflet
// Swiper importé via Vite
import "swiper/css/bundle";
window.Alpine = Alpine;
window.Swal = Swal;
Alpine.start();
