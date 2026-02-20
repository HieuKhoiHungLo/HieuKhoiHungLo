/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/*.php",
    "./resources/views/**/*.php",
    "./app/Controllers/**/*.php"
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        heading: ['Montserrat', 'sans-serif'],
      },
      colors: {
        'hvu-red': '#BE1E2D',
        'hvu-gold': '#FFD700',
        'hvu-blue': '#0066FF',
        'hvu-blue-dark': '#0050CC',
        'hvu-accent': '#E11D48',
      }
    },
  },
  plugins: [],
}
