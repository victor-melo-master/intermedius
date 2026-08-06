/**
 * Configuración de Tailwind CSS.
 * Define las rutas de escaneo para purga de estilos no utilizados,
 * extensiones del tema y plugins adicionales.
 *
 * Tokens de color basados en CSS variables (var(--c-*)) definidas en src/index.css.
 * Esto permite el modo oscuro cambiando solo las variables en `.dark`.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          DEFAULT: 'var(--c-navy)',
          dark: 'var(--c-navy-dark)',
        },
        gold: {
          DEFAULT: 'var(--c-gold)',
          dark: 'var(--c-gold-dark)',
          soft: 'var(--c-gold-soft)',
        },
        surface: {
          DEFAULT: 'var(--c-surface)',
          alt: 'var(--c-surface-alt)',
          muted: 'var(--c-surface-muted)',
          soft: 'var(--c-surface-soft)',
        },
        edge: {
          DEFAULT: 'var(--c-edge)',
          strong: 'var(--c-edge-strong)',
        },
        ink: {
          DEFAULT: 'var(--c-ink)',
          muted: 'var(--c-ink-muted)',
          soft: 'var(--c-ink-soft)',
          faint: 'var(--c-ink-faint)',
        },
        heading: 'var(--c-heading)',
        success: {
          DEFAULT: 'var(--c-success)',
          strong: 'var(--c-success-strong)',
          soft: 'var(--c-success-soft)',
          edge: 'var(--c-success-edge)',
        },
        danger: {
          DEFAULT: 'var(--c-danger)',
          strong: 'var(--c-danger-strong)',
          soft: 'var(--c-danger-soft)',
          edge: 'var(--c-danger-edge)',
        },
        warning: {
          DEFAULT: 'var(--c-warning)',
          strong: 'var(--c-warning-strong)',
          soft: 'var(--c-warning-soft)',
          edge: 'var(--c-warning-edge)',
        },
        info: {
          DEFAULT: 'var(--c-info)',
          strong: 'var(--c-info-strong)',
          soft: 'var(--c-info-soft)',
          edge: 'var(--c-info-edge)',
        },
        violet: {
          DEFAULT: 'var(--c-violet)',
          strong: 'var(--c-violet-strong)',
          soft: 'var(--c-violet-soft)',
          edge: 'var(--c-violet-edge)',
        },
        teal: {
          DEFAULT: 'var(--c-teal)',
          strong: 'var(--c-teal-strong)',
          soft: 'var(--c-teal-soft)',
          edge: 'var(--c-teal-edge)',
        },
      },
    },
  },
  plugins: [],
}
