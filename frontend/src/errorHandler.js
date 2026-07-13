/**
 * Manejador global de errores para Vue 3.
 * Captura errores no controlados en componentes y los muestra en consola,
 * además de notificar al usuario si es necesario.
 */
export function setupErrorHandler(app) {
  app.config.errorHandler = (err, instance, info) => {
    console.error('[Error Global]', err)
    console.error('Componente:', instance?.$.type?.name || 'desconocido')
    console.error('Info:', info)

    // Aquí se podría enviar a Sentry u otro servicio de monitoreo
    // if (window.Sentry) { window.Sentry.captureException(err) }

    // Mostrar mensaje al usuario si es un error conocido
    if (err.response?.status === 422) {
      // Errores de validación — ya se manejan en los componentes
      return
    }
    if (err.response?.status === 500) {
      alert('Error interno del servidor. Intente de nuevo más tarde.')
    }
  }

  // Capturar promesas no manejadas
  window.addEventListener('unhandledrejection', (event) => {
    console.error('[Promesa no manejada]', event.reason)
    event.preventDefault()
  })
}
