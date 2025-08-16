// --- Flatpickr réservation ---
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
                dateFormat: "Y-m-d",
                disable: occupiedDates,
                minDate: "today",
                locale: "fr",
            });
        }
    });
}

document.addEventListener("DOMContentLoaded", initFlatpickrReservation);

// Pour Livewire v3 (événement navigated)
document.addEventListener("livewire:navigated", initFlatpickrReservation);
// Pour Livewire v2 (événement message.processed)
document.addEventListener("livewire:load", function () {
    if (window.livewire) {
        window.livewire.hook("message.processed", () => {
            initFlatpickrReservation();
        });
    }
});
import "./bootstrap";

import Alpine from "alpinejs";

window.Alpine = Alpine;

import Swal from "sweetalert2";
window.Swal = Swal;

Alpine.start();

// index.js
import "preline";

import "./echo";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
