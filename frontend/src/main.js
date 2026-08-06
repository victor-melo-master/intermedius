/**
 * Punto de entrada de la aplicación Vue 3.
 * Inicializa la aplicación con Pinia (estado), Vue Router (navegación)
 * y monta el componente raíz en el elemento #app.
 */
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { setupErrorHandler } from './errorHandler.js'
import App from './App.vue'
import router from './router'
import { useThemeStore } from './stores/theme.js'
import './index.css'

const pinia = createPinia()

const app = createApp(App)
app.use(pinia)
app.use(router)

useThemeStore(pinia).init()

app.mount('#app')

setupErrorHandler(app)
