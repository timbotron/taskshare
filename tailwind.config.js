/** @type {import('tailwindcss').Config} */
// TaskShare styling layer (CODE-77 scaffold). Full token set + Tron dark theme land in CODE-88.
// Build with the standalone CLI (no JS build step): see README / `bin/tailwindcss`.
module.exports = {
  // Dark theme is driven by a `data-theme="dark"` attribute on the root (CODE-88),
  // not the OS media query, so `dark:` variants key off that selector.
  darkMode: ['selector', '[data-theme="dark"]'],
  content: [
    './app/templates/**/*.php',
    './www/js/app.js',
  ],
  theme: {
    extend: {
      colors: {
        // Single bright pale-blue accent (Tron). Expanded into tokens in CODE-88.
        accent: '#7FDBFF',
      },
    },
  },
  plugins: [],
}
