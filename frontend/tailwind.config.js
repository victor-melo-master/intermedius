/**
 * Configuración de Tailwind CSS.
 * Define las rutas de escaneo para purga de estilos no utilizados,
 * extensiones del tema y plugins adicionales.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
