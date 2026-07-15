/**
 * Configuración de Vite para el frontend.
 * Define el plugin de Vue, el puerto del servidor de desarrollo,
 * el proxy inverso hacia la API y el directorio de salida para producción.
 */
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'https://api.intermediusg.com',
        changeOrigin: true,
        secure: false,
      }
    }
  },
  build: {
    outDir: 'dist',
  },
  test: {
    globals: true,
    environment: 'jsdom',
    coverage: {
      reporter: ['text', 'json', 'html'],
      include: ['src/composables/**/*.js', 'src/components/**/*.vue'],
    },
    include: ['src/**/__tests__/*.test.js'],
  },
})
