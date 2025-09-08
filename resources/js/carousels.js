// resources/js/carousels.js
// Gestion centralisée des carrousels Swiper (propriétés & villes)
// Appelé automatiquement via import dans app.js
import Swiper from "swiper/bundle";
import "swiper/css/bundle";

let propertySwiper = null;
let citiesSwiper = null;

function destroyCarousels() {
    if (propertySwiper) {
        propertySwiper.destroy(true, true);
        propertySwiper = null;
    }
    if (citiesSwiper) {
        citiesSwiper.destroy(true, true);
        citiesSwiper = null;
    }
}

function initializeCarousels() {
    destroyCarousels();
    setTimeout(function () {
        // Carrousel des propriétés
        const propertyCarousel = document.querySelector(".property-carousel");
        if (propertyCarousel) {
            propertySwiper = new Swiper(".property-carousel", {
                slidesPerView: 1,
                spaceBetween: 10,
                observer: true,
                observeParents: true,
                watchOverflow: true,
                navigation: {
                    nextEl: ".property-carousel .swiper-button-next",
                    prevEl: ".property-carousel .swiper-button-prev",
                },
                pagination: {
                    el: ".property-carousel .swiper-pagination",
                    clickable: true,
                },
                breakpoints: {
                    640: { slidesPerView: 2, spaceBetween: 20 },
                    768: { slidesPerView: 3, spaceBetween: 30 },
                    1024: { slidesPerView: 4, spaceBetween: 40 },
                },
            });
        }
        // Carrousel des villes
        const citiesCarousel = document.querySelector(".cities-carousel");
        if (citiesCarousel) {
            citiesSwiper = new Swiper(".cities-carousel", {
                slidesPerView: 1,
                spaceBetween: 15,
                observer: true,
                observeParents: true,
                watchOverflow: true,
                navigation: {
                    nextEl: ".cities-carousel .swiper-button-next",
                    prevEl: ".cities-carousel .swiper-button-prev",
                },
                pagination: {
                    el: ".cities-carousel .swiper-pagination",
                    clickable: true,
                },
                breakpoints: {
                    480: { slidesPerView: 2, spaceBetween: 15 },
                    640: { slidesPerView: 3, spaceBetween: 20 },
                    768: { slidesPerView: 4, spaceBetween: 25 },
                    1024: { slidesPerView: 5, spaceBetween: 30 },
                },
            });
        }
    }, 300);
}

// Initialisation au chargement
if (typeof window !== "undefined") {
    document.addEventListener("DOMContentLoaded", initializeCarousels);
    // Livewire v3
    document.addEventListener("livewire:init", () => {
        if (window.Livewire) {
            window.Livewire.on("refresh-carousels", () => {
                setTimeout(() => {
                    initializeCarousels();
                }, 500);
            });
        }
    });
    // Livewire v2 fallback
    if (typeof window.livewire !== "undefined") {
        window.livewire.on("refresh-carousels", () => {
            setTimeout(() => {
                initializeCarousels();
            }, 500);
        });
    }
}

// Pour Livewire v2/v3 : réinitialise après chaque update du DOM
if (window.Livewire) {
    window.Livewire.hook &&
        window.Livewire.hook("message.processed", () => {
            setTimeout(() => {
                initializeCarousels();
            }, 300);
        });
}
if (window.livewire) {
    window.livewire.hook &&
        window.livewire.hook("message.processed", () => {
            setTimeout(() => {
                initializeCarousels();
            }, 300);
        });
}

// Export pour usage éventuel
export { destroyCarousels, initializeCarousels };
