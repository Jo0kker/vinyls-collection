import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/forum/blade-tailwind/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                primary: '#1a1a2e', // bleu nuit
                secondary: '#16213e', // bleu foncé
                accent: '#e94560', // rouge vinyle
                background: '#181818', // noir/gris foncé
                vinyl: '#f5d061', // jaune étiquette vinyle
            },
            fontFamily: {
                sans: ['Bebas Neue', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
