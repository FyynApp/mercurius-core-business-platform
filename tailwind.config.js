/** @type {import('tailwindcss').Config} */

const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './assets/**/*.ts',
        './templates/**/*.html.twig',

        './src/**/stimulus-controllers/**/*.html.twig',
        './src/**/templates/**/*.html.twig',
    ],
    safelist: [
        'md:col-span-1',
        'md:col-span-2',
        'md:col-span-3',
        'md:col-span-4',
        'md:col-span-5',
        'md:col-span-6',
        'md:col-span-7',
        'md:col-span-8',
        'md:col-span-9',
        'md:col-span-10',
        'md:col-span-11',
        'md:col-span-12',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter var', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'spin-slow': 'spin 6s linear infinite',
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ]
}
