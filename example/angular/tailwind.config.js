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
        extend: {},
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            black: colors.black,
            white: colors.white,
            gray: colors.trueGray,
            indigo: colors.indigo,
            red: colors.rose,
            yellow: colors.amber,
        }
    },
    variants: {
        extend: {},
    },
    plugins: [require('@tailwindcss/typography')],
};
