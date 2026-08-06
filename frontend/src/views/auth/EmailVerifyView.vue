<template>
  <div class="min-h-screen bg-surface-alt flex items-center justify-center p-4">
    <div class="bg-surface rounded-2xl shadow-lg p-8 text-center max-w-sm w-full border border-edge">
      <img :src="theme.isDark ? logoNegativo : logoPositivo" alt="Intermedius" class="h-10 w-auto mx-auto mb-6" />
      <div v-if="loading" class="text-ink-muted">Verificando...</div>
      <div v-else-if="success" class="text-success">
        <div class="text-4xl mb-4"><Iconoir name="check" class="w-10 h-10 text-success" /></div>
        <p class="font-bold text-lg text-heading">¡Correo verificado!</p>
        <p class="text-sm text-ink-muted mt-2">Ya puedes iniciar sesión.</p>
        <router-link to="/login" class="mt-4 inline-block px-4 py-2 bg-gold text-white hover:bg-gold-dark rounded-lg font-medium">Iniciar sesión</router-link>
      </div>
      <div v-else class="text-danger">
        <div class="text-4xl mb-4"><Iconoir name="x-mark" class="w-10 h-10 text-danger" /></div>
        <p class="font-bold text-lg text-heading">Enlace inválido</p>
        <p class="text-sm text-ink-muted mt-2">{{ error || 'El enlace de verificación es inválido o ha expirado.' }}</p>
        <router-link to="/login" class="mt-4 inline-block px-4 py-2 bg-edge-strong text-heading hover:opacity-80 rounded-lg font-medium">Volver al login</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Iconoir from '../../components/common/Iconoir.vue'
import { useRoute } from 'vue-router'
import { useThemeStore } from '../../stores/theme.js'
import api from '../../api/axios.js'
import logoPositivo from '../../assets/logo-positivo.png'
import logoNegativo from '../../assets/logo-negativo.png'

const route = useRoute()
const theme = useThemeStore()
const loading = ref(true)
const success = ref(false)
const error = ref('')

onMounted(async () => {
  const { email, hash } = route.query
  if (!email || !hash) {
    error.value = 'Parámetros de verificación faltantes.'
    loading.value = false
    return
  }
  try {
    await api.post('/auth/verificar-email', { email, hash })
    success.value = true
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al verificar el correo.'
  } finally {
    loading.value = false
  }
})
</script>
