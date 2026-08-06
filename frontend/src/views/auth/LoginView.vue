<template>
  <div class="min-h-screen bg-gradient-to-br from-navy to-navy-dark dark:from-surface dark:to-surface-alt flex items-center justify-center p-4 relative">
    <button @click="theme.toggle()" :title="theme.isDark ? 'Modo claro' : 'Modo oscuro'"
      class="absolute top-4 right-4 p-2 rounded-lg text-white/70 dark:text-ink-muted hover:bg-white/10 dark:hover:bg-surface-muted hover:text-white dark:hover:text-heading transition"
      aria-label="Cambiar tema">
      <Iconoir :name="theme.isDark ? 'sun' : 'moon'" class="w-5 h-5" />
    </button>

    <div class="w-full max-w-sm bg-surface rounded-2xl shadow-lg p-8 border border-edge">
      <div class="text-center mb-8">
        <img :src="theme.isDark ? logoNegativo : logoPositivo" alt="Intermedius" class="h-12 w-auto mx-auto mb-4" />
        <p class="text-sm text-ink-muted">Sistema de Casa de Cambio</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-ink mb-1">Correo o usuario</label>
          <input v-model="form.login" type="text" required autocomplete="username"
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold focus:border-gold outline-none transition bg-surface text-ink placeholder:text-ink-soft"
            placeholder="admin@test.com o admin" />
        </div>
        <div>
          <label class="block text-sm font-medium text-ink mb-1">Contraseña</label>
          <input v-model="form.password" type="password" required autocomplete="current-password"
            class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold focus:border-gold outline-none transition bg-surface text-ink placeholder:text-ink-soft"
            placeholder="••••••••" />
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
 * Formulario de usuario/contraseña (acepta email o username) que consume
 * el store de autenticación y redirige al dashboard en caso de éxito.
 * Incluye toggle de tema claro/oscuro.
 */
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import { useThemeStore } from '../../stores/theme.js'
import Iconoir from '../../components/common/Iconoir.vue'
import logoPositivo from '../../assets/logo-positivo.png'
import logoNegativo from '../../assets/logo-negativo.png'

/** Router para redirigir tras login exitoso */
const router = useRouter()
/** Store de autenticación */
const auth = useAuthStore()
/** Store de tema para la variante de logo y el modo claro/oscuro */
const theme = useThemeStore()
/** Datos del formulario de login */
const form = reactive({ login: '', password: '' })

/**
 * Envía las credenciales al store de autenticación.
 * Si es exitoso, redirige al dashboard.
 * @returns {Promise<void>}
 */
async function submit() {
  const ok = await auth.login(form.login, form.password)
  if (ok) router.push('/dashboard')
}
</script>
