/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.php",
    "./Pages/**/*.html",
    "./Pages/**/*.php",
    "./src/**/*.{js,ts,jsx,tsx,vue,php}",
    "./**/*.{html,js,php}"
  ],
  theme: {
    extend: {
      colors: {
        orange: {
          DEFAULT: '#F97316',
          light: '#FFEDD5',
          50: '#fff7ed',
          100: '#ffedd5',
          200: '#fed7aa',
          300: '#fdba74',
          400: '#fb923c',
          500: '#f97316',
          600: '#ea580c',
          700: '#c2410c',
        },
        sidebar: '#16213E',
        card:    '#1E2A3A',
        bg:      '#F8F9FA',
      },
      fontFamily: {
        sans: ['Inter', 'Manrope', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
