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
                sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Тёмная палитра интерфейса
                yt: {
                    bg: '#1e1f22',           // content/navigation background
                    surface: '#232428',      // свимлейны, лёгкое выделение
                    panel: '#2b2d30',        // popup/sidebar background
                    board: '#2b2d30',        // фон канбан-доски и таймшита (правка пользователя)
                    'board-border': '#232428', // разделители на фоне доски
                    swimlane: '#26282c',     // полоса свимлейна на фоне доски
                    card: '#1e1f22',         // карточка = фон, выделяется рамкой
                    hover: '#212d45',        // hover background (синеватый)
                    selected: '#2e436e',     // selected background
                    border: '#43454a',       // line/borders/tag
                    'border-soft': '#313338',
                    'border-strong': '#5a5d63',
                    text: '#ffffff',
                    muted: '#9da0a8',        // secondary/icon
                    faint: '#7a7e87',
                    // Акцент настраивается пользователем: CSS-переменные в layout
                    accent: 'rgb(var(--accent) / <alpha-value>)',
                    'accent-hover': 'rgb(var(--accent-hover) / <alpha-value>)',
                    link: '#99bbff',
                    danger: '#e5493a',
                    success: '#59a869',
                    warning: '#f5c538',
                    blocked: '#e44899',
                },
            },
            boxShadow: {
                card: '0 0 3px 0 rgba(0, 0, 0, 0.1)',
                modal: '0 4px 16px 0 rgba(0, 0, 0, 0.31), 0 2px 6px 0 rgba(0, 0, 0, 0.37)',
            },
        },
    },

    plugins: [forms],
};
