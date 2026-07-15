import { ref, readonly, onUnmounted } from 'vue'
import api from '@/api/axios'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)
  const data = ref(null)

  let controller = null

  const abort = () => {
    if (controller) {
      controller.abort()
      controller = null
    }
  }

  const execute = async (request, ...args) => {
    abort()
    controller = new AbortController()
    loading.value = true
    error.value = null

    try {
      let response
      if (typeof request === 'function') {
        response = await request(controller.signal, ...args)
      } else {
        response = await api({
          ...request,
          signal: controller.signal,
        })
      }
      data.value = response?.data
      return response
    } catch (err) {
      if (err.name === 'AbortError' || err.code === 'ERR_CANCELED') {
        return null
      }
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
      if (controller) {
        controller = null
      }
    }
  }

  onUnmounted(() => {
    abort()
  })

  return {
    loading: readonly(loading),
    error: readonly(error),
    data: readonly(data),
    execute,
    abort,
  }
}
