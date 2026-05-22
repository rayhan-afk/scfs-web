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
                sans: ['"Plus Jakarta Sans"', "Figtree", ...defaultTheme.fontFamily.sans],
                dm: ["DM Sans", "sans-serif"],
                "headline-lg": ["Geist", "sans-serif"],
                "label-md": ["Geist", "sans-serif"],
                "headline-md": ["Geist", "sans-serif"],
                "display-xl": ["Geist", "sans-serif"],
                "body-lg": ["Geist", "sans-serif"],
                "label-sm": ["Geist", "sans-serif"],
                "headline-lg-mobile": ["Geist", "sans-serif"],
                "body-md": ["Geist", "sans-serif"],
            },
            colors: {
                // Warna bawaan sistem kamu sebelumnya
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
                // Warna tambahan untuk Landing Page Baru
                "secondary-fixed-dim": "#d0bcff",
                "on-surface-variant": "#3c4a42",
                "secondary-container": "#8455ef",
                "tertiary": "#a43a3a",
                "on-secondary-fixed-variant": "#5516be",
                "tertiary-container": "#fc7c78",
                "surface-container": "#e8f0e9",
                "on-error": "#ffffff",
                "secondary-fixed": "#e9ddff",
                "primary-fixed-dim": "#4edea3",
                "outline-variant": "#bbcabf",
                "surface-variant": "#dde4dd",
                "on-secondary-fixed": "#23005c",
                "inverse-surface": "#2b322d",
                "surface": "#f4fbf4",
                "surface-tint": "#006c49",
                "primary-container": "#10b981",
                "secondary": "#6b38d4",
                "on-secondary-container": "#fffbff",
                "primary": "#006c49",
                "surface-container-high": "#e3eae3",
                "tertiary-fixed-dim": "#ffb3af",
                "outline": "#6c7a71",
                "surface-dim": "#d4dcd5",
                "on-background": "#161d19",
                "surface-container-lowest": "#ffffff",
                "inverse-on-surface": "#ebf3eb",
                "on-primary-fixed-variant": "#005236",
                "on-tertiary-fixed": "#410005",
                "surface-container-low": "#eef6ee",
                "error": "#ba1a1a",
                "on-secondary": "#ffffff",
                "on-primary-container": "#00422b",
                "error-container": "#ffdad6",
                "background": "#f4fbf4",
                "inverse-primary": "#4edea3",
                "on-primary": "#ffffff",
                "surface-bright": "#f4fbf4",
                "tertiary-fixed": "#ffdad7",
                "on-tertiary-fixed-variant": "#842225",
                "primary-fixed": "#6ffbbe",
                "on-error-container": "#93000a",
                "surface-container-highest": "#dde4dd",
                "on-tertiary-container": "#711419",
                "on-primary-fixed": "#002113",
                "on-surface": "#161d19",
                "on-tertiary": "#ffffff"
            },
            spacing: {
                "md": "24px",
                "margin-desktop": "64px",
                "gutter": "24px",
                "xs": "8px",
                "lg": "48px",
                "sm": "16px",
                "xl": "80px",
                "margin-mobile": "16px",
                "base": "4px"
            },
            fontSize: {
                "headline-lg": ["36px", { "lineHeight": "44px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                "label-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "500" }],
                "headline-md": ["24px", { "lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "500" }],
                "display-xl": ["60px", { "lineHeight": "72px", "letterSpacing": "-0.02em", "fontWeight": "600" }],
                "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                "headline-lg-mobile": ["28px", { "lineHeight": "36px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }]
            },
            borderRadius: {
                "custom-sm": "0.25rem",
                "custom-lg": "0.5rem",
                "custom-xl": "0.75rem",
            }
        },
    },

    plugins: [forms],
};