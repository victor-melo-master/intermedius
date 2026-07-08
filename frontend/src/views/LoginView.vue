<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-8">
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <span class="text-3xl">💱</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Intermedius</h1>
        <p class="text-sm text-gray-500 mt-1">Sistema de Casa de Cambio</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
          <input v-model="form.email" type="email" required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
            placeholder="admin@test.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
          <input v-model="form.password" type="password" required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
            placeholder="password123" />
        </div>

        <div v-if="auth.error" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg">
          {{ auth.error }}
        </div>

        <button type="submit" :disabled="auth.loading"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-2.5 rounded-xl transition flex items-center justify-center gap-2">
          <span v-if="auth.loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ auth.loading ? 'Entrando...' : 'Iniciar sesión' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
/**
 * LoginView — Pantalla de inicio de sesión del sistema.
 * Formulario simple de email/contraseña que consume el store de autenticación
 * y redirige al dashboard en caso de éxito.
 */
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

/** Router para redirigir tras login exitoso */
const router = useRouter()
/** Store de autenticación */
const auth = useAuthStore()
/** Datos del formulario de login */
const form = reactive({ email: '', password: '' })

/**
 * Envía las credenciales al store de autenticación.
 * Si es exitoso, redirige al dashboard.
 * @returns {Promise<void>}
 */
async function submit() {
  const ok = await auth.login(form.email, form.password)
  if (ok) router.push('/dashboard')
}
</script>
