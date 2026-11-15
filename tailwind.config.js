import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                petrol: {
                    DEFAULT: '#0B3540',
                    900: '#072730',
                    800: '#0B3540',
                    700: '#114654',
                    600: '#175A6B',
                },
                turquoise: {
                    DEFAULT: '#147B86',
                    700: '#0F6169',
                    600: '#147B86',
                    500: '#1A96A3',
                    100: '#DDF0F2',
                },
                warm: {
                    DEFAULT: '#FAF8F5',
                    100: '#F4F1EC',
                    200: '#E9E3DA',
                    300: '#D8D0C4',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px rgba(11, 53, 64, 0.06), 0 8px 24px rgba(11, 53, 64, 0.06)',
            },
        },
    },

    plugins: [forms],
};
