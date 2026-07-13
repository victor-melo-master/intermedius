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
import './index.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')

setupErrorHandler(app)
