<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-heading">Mi perfil</h2>

    <!-- Toast -->
    <Transition name="fade">
      <div v-if="toast.msg" class="px-4 py-3 rounded-xl text-sm font-medium"
        :class="toast.tipo === 'error' ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success'">
        {{ toast.msg }}
      </div>
    </Transition>

    <!-- Aviso de correo sin verificar -->
    <div v-if="emailPendienteVerificacion" class="px-4 py-3 rounded-xl text-sm bg-warning-soft text-warning-strong">
      Tu correo cambió a <strong>{{ perfil.email }}</strong>. Te enviamos un enlace de verificación; debes
      verificarlo antes del próximo inicio de sesión.
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <!-- ── Datos de perfil ─────────────────────────────────────── -->
      <div class="lg:col-span-2 bg-white dark:bg-surface rounded-xl border border-edge p-5 space-y-4">
        <h3 class="font-semibold text-ink">Datos de perfil</h3>

        <!-- Avatar -->
        <div class="flex items-center gap-4">
          <div class="relative shrink-0">
            <img v-if="avatarPreview || avatarUrl(perfil)"
              :src="avatarPreview || avatarUrl(perfil)"
              :alt="`Avatar de ${perfil.name}`"
              class="w-20 h-20 rounded-full object-cover border border-edge" />
            <div v-else
              class="w-20 h-20 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark text-3xl font-semibold">
              {{ (perfil.name || '?').charAt(0).toUpperCase() }}
            </div>
          </div>
          <div class="flex-1">
            <label class="block text-sm text-ink-muted mb-1">Foto de perfil</label>
            <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
              @change="onAvatarSelected"
              class="block w-full text-sm text-ink-muted file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gold-soft file:text-gold-dark file:font-semibold hover:file:bg-gold/20 cursor-pointer" />
            <p v-if="avatarError" class="text-xs text-danger mt-1">{{ avatarError }}</p>
            <button v-if="avatarFile" type="button" @click="limpiarAvatar"
              class="text-xs font-medium text-danger hover:text-danger-strong underline mt-1">
              Quitar selección
            </button>
          </div>
        </div>

        <!-- Nombre (no editable) -->
        <div>
          <label class="block text-sm text-ink-muted mb-1">Nombre de usuario</label>
          <input :value="perfil.name" type="text" disabled
            class="w-full px-3 py-2 border border-edge rounded-lg bg-surface-muted text-ink-muted cursor-not-allowed" />
        </div>

        <!-- Correo -->
        <div>
          <label class="block text-sm text-ink-muted mb-1">Correo electrónico</label>
          <input v-model="form.email" type="email" placeholder="correo@ejemplo.com"
            class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
          <p v-if="emailError" class="text-xs text-danger mt-1">{{ emailError }}</p>
        </div>

        <!-- Teléfono -->
        <div>
          <label class="block text-sm text-ink-muted mb-1">Teléfono de contacto (opcional)</label>
          <input v-model="form.telefono" type="tel" placeholder="+58 412 123 4567"
            class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        </div>

        <div class="flex items-center justify-end gap-3 pt-1">
          <p class="text-sm text-ink-muted mr-auto">Cambiar el correo exige tu contraseña actual.</p>
          <button @click="guardarDatos" :disabled="guardando"
            class="px-5 py-2 bg-gold text-white rounded-lg text-sm font-semibold hover:bg-gold-dark disabled:opacity-50 transition flex items-center gap-2">
            <span v-if="guardando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Guardar datos
          </button>
        </div>
      </div>

      <!-- ── Columna derecha ─────────────────────────────────────── -->
      <div class="space-y-4">
        <!-- Tipo de usuario (no editable) -->
        <div class="bg-white dark:bg-surface rounded-xl border border-edge p-5">
          <h3 class="font-semibold text-ink mb-3">Tipo de usuario</h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="rol in perfil.roles" :key="rol"
              class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full"
              :class="roleBadgeClass(rol)">
              <Iconoir :name="roleIcon(rol)" class="w-3 h-3" />
              {{ roleLabels[rol] || rol }}
            </span>
          </div>
          <p class="text-sm text-ink-muted mt-3">El tipo de usuario lo asigna un administrador y no se modifica aquí.</p>
        </div>

        <!-- Cambiar contraseña -->
        <div class="bg-white dark:bg-surface rounded-xl border border-edge p-5 space-y-3">
          <h3 class="font-semibold text-ink">Cambiar contraseña</h3>
          <div>
            <label class="block text-sm text-ink-muted mb-1">Contraseña actual</label>
            <div class="relative">
              <input v-model="pass.actual" :type="mostrarPass.actual ? 'text' : 'password'" placeholder="••••••••"
                class="w-full px-3 py-2 pr-10 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              <button type="button" @click="mostrarPass.actual = !mostrarPass.actual" class="absolute right-2 top-1/2 -translate-y-1/2 text-ink-muted hover:text-ink">
                <Iconoir :name="mostrarPass.actual ? 'eye-slash' : 'eye'" class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div>
            <label class="block text-sm text-ink-muted mb-1">Nueva contraseña</label>
            <div class="relative">
              <input v-model="pass.nueva" :type="mostrarPass.nueva ? 'text' : 'password'" placeholder="••••••••"
                class="w-full px-3 py-2 pr-10 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              <button type="button" @click="mostrarPass.nueva = !mostrarPass.nueva" class="absolute right-2 top-1/2 -translate-y-1/2 text-ink-muted hover:text-ink">
                <Iconoir :name="mostrarPass.nueva ? 'eye-slash' : 'eye'" class="w-4 h-4" />
              </button>
            </div>
            <ul class="mt-2 space-y-1">
              <li v-for="req in passwordRequisitos" :key="req.label" class="text-xs"
                :class="req.cumplido ? 'text-success' : 'text-ink-muted'">
                {{ req.cumplido ? '✓' : '○' }} {{ req.label }}
              </li>
            </ul>
          </div>
          <div>
            <label class="block text-sm text-ink-muted mb-1">Confirmar nueva contraseña</label>
            <div class="relative">
              <input v-model="pass.confirmacion" :type="mostrarPass.confirmacion ? 'text' : 'password'" placeholder="••••••••"
                class="w-full px-3 py-2 pr-10 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
              <button type="button" @click="mostrarPass.confirmacion = !mostrarPass.confirmacion" class="absolute right-2 top-1/2 -translate-y-1/2 text-ink-muted hover:text-ink">
                <Iconoir :name="mostrarPass.confirmacion ? 'eye-slash' : 'eye'" class="w-4 h-4" />
              </button>
            </div>
          </div>
          <button @click="cambiarPassword" :disabled="cambiandoPass || !passValido"
            class="w-full py-2 bg-navy text-white dark:text-white dark:bg-gold rounded-lg text-sm font-semibold hover:opacity-90 disabled:opacity-50 transition flex items-center justify-center gap-2">
            <span v-if="cambiandoPass" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Cambiar contraseña
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Vista de perfil del usuario autenticado.
 * Permite modificar correo, teléfono, foto de perfil y contraseña.
 * El tipo de usuario (rol) se muestra solo de lectura.
 */
import { ref, reactive, computed } from 'vue'
import api from '../../api/axios.js'
import { useAuthStore } from '../../stores/auth.js'
import { useApiError } from '../../composables/useApiError.js'
import Iconoir from '../../components/common/Iconoir.vue'

const auth = useAuthStore()
const { parseError } = useApiError()

/** Perfil del usuario autenticado (copia local reactiva del store). */
const perfil = ref(auth.user || {})

/** Formulario de datos de perfil */
const form = reactive({
  email: perfil.value.email || '',
  telefono: perfil.value.telefono || '',
})

/** Formulario de contraseña */
const pass = reactive({ actual: '', nueva: '', confirmacion: '' })
/** Visibilidad de los campos de contraseña */
const mostrarPass = reactive({ actual: false, nueva: false, confirmacion: false })

/** Estados de guardado */
const guardando = ref(false)
const cambiandoPass = ref(false)

/** Mensaje flotante de resultado */
const toast = ref({ msg: '', tipo: 'ok' })
let toastTimer = null
function mostrarToast(msg, tipo = 'ok') {
  toast.value = { msg, tipo }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = { msg: '', tipo: 'ok' } }, 5000)
}

/** Indica si el correo cambió y quedó pendiente de verificación */
const emailPendienteVerificacion = ref(false)

/** Error de formato del correo */
const emailError = ref('')

/**
 * URL autenticada del avatar (imagen WebP servida por la API).
 * @param {Object} u - Usuario con { id, avatar_path }
 * @returns {string|null}
 */
function avatarUrl(u) {
  if (!u?.avatar_path) return null
  const token = localStorage.getItem('token')
  return `${import.meta.env.VITE_API_URL}/usuarios/${u.id}/avatar?token=${token}&v=${encodeURIComponent(u.avatar_path)}`
}

/** Tipos de imagen aceptados para el avatar (se convierten a WebP en el backend) */
const avatarTiposAceptados = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp']
/** Archivo de avatar seleccionado (aún sin subir) */
const avatarFile = ref(null)
/** Vista previa local del avatar (URL de objeto) */
const avatarPreview = ref('')
/** Error de validación del archivo de avatar */
const avatarError = ref('')

/**
 * Maneja la selección de un archivo de avatar: valida tipo y tamaño y genera vista previa.
 * @param {Event} evt - Evento change del input file
 */
function onAvatarSelected(evt) {
  const file = evt.target.files?.[0]
  avatarError.value = ''
  if (!file) return
  if (!avatarTiposAceptados.includes(file.type)) {
    avatarError.value = 'Formato no permitido. Usa JPG, PNG, GIF, WebP o BMP.'
    avatarFile.value = null
    avatarPreview.value = ''
    evt.target.value = ''
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'La imagen supera los 2MB. Elige una más pequeña.'
    avatarFile.value = null
    avatarPreview.value = ''
    evt.target.value = ''
    return
  }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

/** Limpia la selección de avatar */
function limpiarAvatar() {
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarFile.value = null
  avatarPreview.value = ''
  avatarError.value = ''
}

/** Valida que el correo tenga formato correcto */
const esEmailValido = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim())

/**
 * Guarda los datos de perfil (correo, teléfono y opcionalmente avatar).
 * Si el correo cambió, se exige la contraseña actual en el backend.
 * @returns {Promise<void>}
 */
async function guardarDatos() {
  emailError.value = ''
  if (!esEmailValido(form.email)) {
    emailError.value = 'Ingresa un correo electrónico válido.'
    return
  }
  guardando.value = true
  try {
    const payload = new FormData()
    payload.append('email', form.email.trim().toLowerCase())
    payload.append('telefono', (form.telefono || '').trim())
    if (avatarFile.value) payload.append('avatar', avatarFile.value)

    const { data } = await api.patch('/perfil', payload)

    const correoCambio = data.email !== auth.user?.email
    if (correoCambio) emailPendienteVerificacion.value = true

    auth.actualizarUsuario(data)
    perfil.value = data
    limpiarAvatar()
    mostrarToast(correoCambio ? 'Perfil actualizado. Verifica tu nuevo correo con el enlace enviado.' : 'Perfil actualizado correctamente.')
  } catch (err) {
    const mensaje = parseError(err)
    if (mensaje.toLowerCase().includes('contraseña actual')) {
      emailError.value = mensaje
    }
    mostrarToast(mensaje, 'error')
  } finally {
    guardando.value = false
  }
}

/**
 * Requisitos de la contraseña (alineados con Password::min(8)->mixedCase()->numbers()->symbols()).
 * @type {import('vue').ComputedRef<Array<{label: string, cumplido: boolean}>>}
 */
const passwordRequisitos = computed(() => {
  const p = pass.nueva
  return [
    { label: 'Mínimo 8 caracteres', cumplido: p.length >= 8 },
    { label: 'Mayúsculas y minúsculas', cumplido: /[a-z]/.test(p) && /[A-Z]/.test(p) },
    { label: 'Al menos un número', cumplido: /\d/.test(p) },
    { label: 'Al menos un símbolo', cumplido: /[^A-Za-z0-9]/.test(p) },
  ]
})

/** Indica si el formulario de contraseña es válido (todos los campos completos y confirmación igual) */
const passValido = computed(() => {
  const requisitos = passwordRequisitos.value
  return !!pass.actual && requisitos.every((r) => r.cumplido) && pass.nueva === pass.confirmacion
})

/**
 * Cambia la contraseña del usuario autenticado.
 * @returns {Promise<void>}
 */
async function cambiarPassword() {
  cambiandoPass.value = true
  try {
    await api.patch('/perfil', {
      password_actual: pass.actual,
      password: pass.nueva,
      password_confirmation: pass.confirmacion,
    })
    pass.actual = ''
    pass.nueva = ''
    pass.confirmacion = ''
    mostrarToast('Contraseña actualizada correctamente.')
  } catch (err) {
    mostrarToast(parseError(err), 'error')
  } finally {
    cambiandoPass.value = false
  }
}

/** Clases de badge según el rol */
const roleBadgeClass = (rol) => ({
  'super_admin': 'bg-danger-soft text-danger-strong',
  'admin': 'bg-warning-soft text-warning-strong',
  'operador': 'bg-info-soft text-info-strong',
  'pagador': 'bg-violet-soft text-violet-strong',
  'contador': 'bg-success-soft text-success-strong',
  'lectura': 'bg-surface-muted text-ink-muted',
}[rol] || 'bg-surface-muted text-ink-muted')

/** Icono según el rol */
const roleIcon = (rol) => ({
  'super_admin': 'key',
  'admin': 'shield-check',
  'operador': 'arrows-right-left',
  'pagador': 'banknotes',
  'contador': 'calculator',
  'lectura': 'eye',
}[rol] || 'users')

/** Etiquetas legibles de los roles */
const roleLabels = {
  'super_admin': 'Super Administrador',
  'admin': 'Administrador',
  'operador': 'Operador',
  'pagador': 'Pagador',
  'contador': 'Contador',
  'lectura': 'Lectura',
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
