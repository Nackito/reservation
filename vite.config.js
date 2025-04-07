import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/style.css",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "vanilla-calendar": "vanilla-calendar/src/index.js",
        },
    },
});
