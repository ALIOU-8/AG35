import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Couleurs du drapeau guinéen.
                guinea: {
                    red: '#CE1126',
                    yellow: '#FCD116',
                    green: '#009460',
                },
                brand: {
                    50: '#e6f6ef',
                    100: '#c2e9d8',
                    200: '#8fd6b8',
                    300: '#52bf93',
                    400: '#22a876',
                    500: '#009460',
                    600: '#007f53',
                    700: '#006744',
                    800: '#005036',
                    900: '#003b28',
                },
            },
        },
    },

    plugins: [forms],
};
