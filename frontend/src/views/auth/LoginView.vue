<template>
  <div class="min-h-screen bg-gradient-to-br from-navy to-navy-dark flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-surface rounded-2xl shadow-lg p-8 border border-edge">
      <div class="text-center mb-8">
        <img :src="theme.isDark ? logoNegativo : logoPositivo" alt="Intermedius" class="h-12 w-auto mx-auto mb-4" />
        <p class="text-sm text-ink-muted">Sistema de Casa de Cambio</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-ink mb-1">Correo electrónico</label>
          <input v-model="form.email" type="email" required
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold focus:border-gold outline-none transition bg-surface text-ink placeholder:text-ink-soft"
            placeholder="admin@test.com" />
        </div>
        <div>
          <label class="block text-sm font-medium text-ink mb-1">Contraseña</label>
          <input v-model="form.password" type="password" required
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold focus:border-gold outline-none transition bg-surface text-ink placeholder:text-ink-soft"
            placeholder="password123" />
        </div>

        <div v-if="auth.error" class="bg-danger-soft text-danger text-sm p-3 rounded-lg">
          {{ auth.error }}
        </div>

        <button type="submit" :disabled="auth.loading"
          class="w-full bg-gold hover:bg-gold-dark disabled:opacity-50 text-navy font-semibold py-2.5 rounded-xl transition flex items-center justify-center gap-2">
          <span v-if="auth.loading" class="w-5 h-5 border-2 border-navy/30 border-t-navy rounded-full animate-spin"></span>
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
import { useAuthStore } from '../../stores/auth.js'
import { useThemeStore } from '../../stores/theme.js'
import logoPositivo from '../../assets/logo-positivo.png'
import logoNegativo from '../../assets/logo-negativo.png'

/** Router para redirigir tras login exitoso */
const router = useRouter()
/** Store de autenticación */
const auth = useAuthStore()
/** Store de tema para elegir la variante de logo */
const theme = useThemeStore()
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
