/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "node_modules/preline/dist/*.js",
        "./resources/css/filament/admin/theme.css",
        "./vendor/filament/**/*.blade.php",
    ],
    darkMode: "class", // active le mode sombre via la classe .dark
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: "#2563eb", // bleu clair
                    dark: "#1e293b", // bleu foncé pour dark
                },
                secondary: {
                    DEFAULT: "#9333EA",
                    dark: "#a78bfa",
                },
                background: {
                    DEFAULT: "#f9fafb",
                    dark: "#18181b",
                },
                surface: {
                    DEFAULT: "#fff",
                    dark: "#23272f",
                },
                // Ajoute d'autres couleurs personnalisées ici
            },
        },
    },
    plugins: [require("@tailwindcss/forms")],
};
