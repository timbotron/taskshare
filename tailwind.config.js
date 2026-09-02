/** @type {import('tailwindcss').Config} */
// TaskShare styling layer. Semantic colors are CSS variables (see tailwind/input.css),
// so a single set of component rules themes light/dark by flipping the variables.
// Build with the standalone CLI (no JS build step): see README / `bin/tailwindcss`.
module.exports = {
  // Dark theme is driven by `data-theme="dark"` on the root; OS prefers-color-scheme
  // is the fallback (handled in the base layer).
  darkMode: ['selector', '[data-theme="dark"]'],
  content: [
    './templates/**/*.php',
    './public/js/app.js',
  ],
  theme: {
    extend: {
      colors: {
        accent: 'var(--accent)',
        'accent-fg': 'var(--accent-fg)',
        app: 'var(--bg)',
        surface: 'var(--surface)',
        fg: 'var(--fg)',
        muted: 'var(--muted)',
        line: 'var(--border)',
      },
    },
  },
  plugins: [],
}
