// --- Flatpickr réservation ---
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { French } from "flatpickr/dist/l10n/fr.js";

function initFlatpickrReservation() {
    if (typeof flatpickr !== "function") return;
    const occupiedDates = window.occupiedDates || [];
    [
        "#ReservationCheckIn",
        "#ReservationCheckOut",
        "#ReservationCheckInBottom",
        "#ReservationCheckOutBottom",
    ].forEach((selector) => {
        const el = document.querySelector(selector);
        if (el) {
            flatpickr(el, {
                dateFormat: "Y-m-d", // format de la valeur envoyée à Livewire
                altInput: true,
                altFormat: "d-m-Y", // affichage utilisateur : jour-mois-année
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
