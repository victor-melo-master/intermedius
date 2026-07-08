/**
 * Configuración centralizada de Axios.
 * Define la instancia HTTP con baseURL, headers comunes,
 * interceptor de request para adjuntar token JWT,
 * e interceptor de response para redirigir al login en 401.
 */
import axios from 'axios'

/** Instancia de Axios preconfigurada. */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://api.intermediusg.com/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30000,
})

/** Adjunta el token JWT del localStorage a cada petición. */
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/** Maneja errores globales: redirige a /login si la respuesta es 401. */
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default api
