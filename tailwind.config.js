/** @type {import('tailwindcss').Config} */

const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
  content: ['./templates/**/*.html.twig', './assets/**/*.ts'],
  safelist: [
    'sm:col-span-1',
    'sm:col-span-2',
    'sm:col-span-3',
    'sm:col-span-4',
    'sm:col-span-5',
    'sm:col-span-6',
    'sm:col-span-7',
    'sm:col-span-8',
    'sm:col-span-9',
    'sm:col-span-10',
    'sm:col-span-11',
    'sm:col-span-12',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter var', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ]
}
