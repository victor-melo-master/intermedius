<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center max-w-sm w-full">
      <div v-if="loading" class="text-gray-500">Verificando...</div>
      <div v-else-if="success" class="text-green-600">
        <div class="text-4xl mb-4">✅</div>
        <p class="font-bold text-lg">¡Correo verificado!</p>
        <p class="text-sm text-gray-500 mt-2">Ya puedes iniciar sesión.</p>
        <router-link to="/login" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg">Iniciar sesión</router-link>
      </div>
      <div v-else class="text-red-600">
        <div class="text-4xl mb-4">❌</div>
        <p class="font-bold text-lg">Enlace inválido</p>
        <p class="text-sm text-gray-500 mt-2">{{ error || 'El enlace de verificación es inválido o ha expirado.' }}</p>
        <router-link to="/login" class="mt-4 inline-block px-4 py-2 bg-gray-600 text-white rounded-lg">Volver al login</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios.js'

const route = useRoute()
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
