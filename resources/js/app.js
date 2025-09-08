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
                //conjunction: " to ", // force le séparateur pour Livewire
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
import "./carousels";
window.Alpine = Alpine;
window.Swal = Swal;
Alpine.start();

// Gestion du thème sombre
window.applyTheme = function (userTheme = null) {
    // Si la valeur n'est pas passée, essaie de la récupérer via un meta ou data-attribute
    if (!userTheme) {
        const meta = document.querySelector('meta[name="user-theme"]');
        userTheme = meta ? meta.content : "light";
    }
    if (userTheme === "dark") {
        document.documentElement.classList.add("dark");
    } else if (userTheme === "light") {
        document.documentElement.classList.remove("dark");
    } else if (userTheme === "system") {
        if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    }
};
window.applyTheme();

window
    .matchMedia("(prefers-color-scheme: dark)")
    .addEventListener("change", () => {
        window.applyTheme();
    });

// Pour le switch instantané
window.toggleTheme = function () {
    document.documentElement.classList.toggle("dark");
};
