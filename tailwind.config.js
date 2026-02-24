import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
                dm: ["DM Sans", "sans-serif"],
            },
            colors: {
                brand: {
                    purple: "#4318FF",
                    "purple-light": "#868CFF",
                    text: "#2B3674",
                    "text-light": "#A3AED0",
                    bg: "#F4F7FE",
                    orange: "#FFB547",
                    red: "#EE5D50",
                    green: "#05CD99",
                },
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
