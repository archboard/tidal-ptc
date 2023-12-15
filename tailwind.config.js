import colors from 'tailwindcss/colors'
import defaultTheme from 'tailwindcss/defaultTheme'

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    'resources/js/**/*.{vue,js}',
    'resources/views/**/*.blade.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: [
          'Inter',
          ...defaultTheme.fontFamily.sans,
        ]
      },
      colors: {
        transparent: 'transparent',
        current: 'currentColor',
        // hard code from the actual color set you want
        primary: {
          ...colors.cyan,
        },
        gray: {
          ...colors.zinc,
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
