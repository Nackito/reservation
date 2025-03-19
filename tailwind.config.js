/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "node_modules/preline/dist/*.js",
    ],
    //darkMode: "class", // or 'media' or 'class'
    theme: {
        extend: {
            colors: {
                primary: "#1E40AF",
                secondary: "#9333EA",
            },
        },
    },
    plugins: [require("@tailwindcss/forms")],
};
