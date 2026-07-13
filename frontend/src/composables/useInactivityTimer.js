import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

export function useInactivityTimer(timeoutMinutes = 30) {
  const router = useRouter()
  const auth = useAuthStore()
  let timer = null

  function resetTimer() {
    clearTimeout(timer)
    timer = setTimeout(() => {
      auth.logout()
      router.push('/login')
    }, timeoutMinutes * 60 * 1000)
  }

  onMounted(() => {
    window.addEventListener('mousemove', resetTimer)
    window.addEventListener('keypress', resetTimer)
    window.addEventListener('click', resetTimer)
    resetTimer()
  })

  onUnmounted(() => {
    clearTimeout(timer)
    window.removeEventListener('mousemove', resetTimer)
    window.removeEventListener('keypress', resetTimer)
    window.removeEventListener('click', resetTimer)
  })
}
