const colors = require('tailwindcss/colors');

module.exports = {
    prefix: '',
    important: true,
    purge: {
        enabled: process.env.NODE_ENV === 'production',
        content: [
            './src/**/*.{html,ts}',
        ]
    },
    darkMode: 'class', // or 'media' or 'class'
    theme: {
        extend: {
            blur: {
                xs: '2px',
            },
            colors: {
                transparent: 'transparent',
                current: 'currentColor',
                black: colors.black,
                white: colors.white,
                gray: colors.trueGray,
                indigo: colors.indigo,
                red: colors.rose,
                yellow: colors.amber,
                blue: colors.blue
            },
            zIndex: {
                '-10': '-10',
            },
            textColor: {
                'primary': '#3f51b5',
                'secondary': '#ff4081',
                'danger': '#f44336',
                'success': '#4ec257',
                'cta': '#3f51b5'
            },
            backgroundColor: {
                'primary': '#3f51b5',
                'secondary': '#ff4081',
                'danger': '#f44336',
                'success': '#4ec257'
            }
        }
    },
    variants: {
        extend: {
            opacity: ['disabled'],
        },
    },
    plugins: [require('@tailwindcss/typography')],
};
