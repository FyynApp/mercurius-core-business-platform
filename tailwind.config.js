/** @type {import('tailwindcss').Config} */

const defaultTheme = require('tailwindcss/defaultTheme');

const colors = require('tailwindcss/colors')

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
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            black: colors.black,
            white: colors.white,
            gray: colors.gray,
            neutral: colors.neutral,
            lime: colors.lime,
            red: colors.red,
            orange: colors.orange,
            'mercurius-green': colors.lime,
            'mercurius-blue': {
                50:  '#d8f2fe',
                100: '#ade5fe',
                200: '#79d4fc',
                300: '#3ac4ff',
                400: '#00b0fc',
                500: '#009ee2',
                600: '#0185bd',
                700: '#026c9a',
                800: '#015072',
                900: '#00354c'
            },
        },
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
