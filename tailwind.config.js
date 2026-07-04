import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './app/Enums/**/*.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Segoe UI', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Палитра в духе тёмной темы YouTrack
                yt: {
                    bg: '#1e1f22',
                    surface: '#26282e',
                    panel: '#2b2d33',
                    card: '#30323a',
                    hover: '#393b42',
                    border: '#43454a',
                    'border-soft': '#33353a',
                    text: '#dfe1e5',
                    muted: '#9da0a8',
                    faint: '#6f737a',
                    accent: '#3574f0',
                    'accent-hover': '#4a88f7',
                    danger: '#e5493a',
                    success: '#23a186',
                    warning: '#ffc84a',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgba(0, 0, 0, 0.35)',
                modal: '0 8px 40px rgba(0, 0, 0, 0.55)',
            },
        },
    },

    plugins: [forms],
};
