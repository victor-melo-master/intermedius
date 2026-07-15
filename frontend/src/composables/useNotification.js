import { ref } from 'vue'

const notifications = ref([])
let idCounter = 0

/**
 * Composable para manejar notificaciones en la UI (toasts).
 */
export function useNotification() {
  const show = (message, type = 'info', duration = 5000) => {
    const id = ++idCounter
    const notification = { id, message, type, duration, visible: true }
    notifications.value.push(notification)

    if (duration > 0) {
      setTimeout(() => {
        hide(id)
      }, duration)
    }

    return id
  }

  const hide = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value[index].visible = false
      setTimeout(() => {
        notifications.value.splice(index, 1)
      }, 300)
    }
  }

  const success = (message, duration) => show(message, 'success', duration)
  const error = (message, duration) => show(message, 'error', duration)
  const warning = (message, duration) => show(message, 'warning', duration)
  const info = (message, duration) => show(message, 'info', duration)

  return {
    notifications,
    show,
    hide,
    success,
    error,
    warning,
    info,
  }
}
