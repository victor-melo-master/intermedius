/**
 * Configuración de Vite para el frontend.
 * Define el plugin de Vue, el puerto del servidor de desarrollo,
 * el proxy inverso hacia la API y el directorio de salida para producción.
 */
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
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
  }
})
