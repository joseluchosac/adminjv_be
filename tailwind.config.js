/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./**/*.php"],
  theme: {
    extend: {},
  },
  plugins: [],
}

// npx tailwindcss -i ./public/tailwind.css -o ./public/assets/css/style.css --watch