<template>
  <div class="space-y-4">
    <div v-if="toast.msg"
      class="fixed top-4 right-4 z-[70] max-w-sm bg-amber-50 border border-amber-300 text-amber-800 text-sm px-4 py-3 rounded-xl shadow-lg">
      {{ toast.msg }}
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Usuarios</h2>
      <button @click="openForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
        <span>+</span> Nuevo usuario
      </button>
    </div>

    <div v-if="usuarios.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="usuarios.error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ usuarios.error }}
      <button @click="usuarios.fetchAll()" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="usuarios.list.length === 0" class="text-center py-16">
      <Iconoir name="users" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
      <p class="text-gray-500">No hay usuarios registrados</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="u in usuarios.list" :key="u.id"
        class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3"
        :class="{ 'opacity-60': !u.activo }">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-700 font-bold text-sm">
          {{ u.name?.charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-sm">{{ u.name }}</p>
            <span v-for="rol in u.roles" :key="rol"
              class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="roleBadgeClass(rol)">
              <Iconoir :name="roleIcon(rol)" class="w-3 h-3" />
              {{ roleLabel(rol) }}
            </span>
          </div>
          <p class="text-xs text-gray-500">{{ u.email }}</p>
          <p v-if="u.titular" class="text-xs text-gray-400">Titular: {{ u.titular.alias }}</p>
          <p v-if="u.last_login_at" class="text-xs text-gray-400">Último acceso: {{ formatDate(u.last_login_at) }}</p>
        </div>
        <div v-if="!isSuperAdmin(u)" class="flex items-center gap-2">
          <!-- Toggle activo -->
          <button @click="handleToggle(u)" :title="u.activo ? 'Desactivar' : 'Activar'"
            class="relative w-10 h-5 rounded-full transition-colors"
            :class="u.activo ? 'bg-green-500' : 'bg-gray-300'">
            <span class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
              :class="u.activo ? 'translate-x-5' : 'translate-x-0.5'"></span>
          </button>
          <button @click="openForm(u)" class="text-xs text-blue-600 hover:text-blue-800 font-medium px-2 py-1 border border-blue-200 rounded-lg hover:bg-blue-50">
            Editar
          </button>
        </div>
      </div>
    </div>

    <AppFormModal v-model="showForm" :title="editing ? 'Editar usuario' : 'Nuevo usuario'" @close="closeForm">
      <form @submit.prevent="submit" novalidate class="space-y-3">
        <!-- Nombre -->
        <div>
          <label class="text-sm text-gray-600 mb-1 block">Nuevo nombre de usuario *</label>
          <input v-model="form.name" required placeholder="Nombre completo"
            @input="nameError = ''; nameOk = ''"
            @blur="validarDisponible('name')"
            :class="inputClass(!!nameError)"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <p v-if="nameOk" class="text-xs text-green-600 mt-1">{{ nameOk }}</p>
          <p v-if="nameError" class="text-xs text-red-600 mt-1">{{ nameError }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="text-sm text-gray-600 mb-1 block">Nuevo correo de usuario *</label>
          <input v-model="form.email" type="email" required placeholder="correo@ejemplo.com"
            @input="emailError = ''; emailOk = ''"
            @blur="validarDisponible('email')"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <p v-if="emailOk" class="text-xs text-green-600 mt-1">{{ emailOk }}</p>
          <p v-if="emailError" class="text-xs text-red-600 mt-1">{{ emailError }}</p>
        </div>

        <!-- Password -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm text-gray-600 block">{{ editing ? 'Nueva contraseña de usuario (opcional)' : 'Nueva contraseña de usuario *' }}</label>
            <button type="button" @click="generarPassword"
              class="text-xs font-medium text-blue-600 hover:text-blue-800 underline">
              Generar contraseña segura
            </button>
          </div>
          <div class="relative">
            <input v-model="form.password" :type="mostrarPassword ? 'text' : 'password'"
              placeholder="Mínimo 8 caracteres, con mayúsculas, números y símbolos"
              :required="!editing"
              class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
            <button type="button" @click="mostrarPassword = !mostrarPassword"
              :title="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
              :aria-label="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
              <Iconoir :name="mostrarPassword ? 'eye-slash' : 'eye'" class="w-5 h-5" />
            </button>
          </div>
          <p v-if="editing" class="text-xs text-gray-400 mt-1">Dejar en blanco para mantener la contraseña actual.</p>
          <ul v-if="!editing || form.password" class="mt-2 space-y-1">
            <li v-for="req in passwordRequisitos" :key="req.label"
              class="flex items-center gap-1.5 text-xs"
              :class="req.cumplido ? 'text-green-600' : 'text-gray-400'">
              <span class="w-3.5 text-center">{{ req.cumplido ? '✓' : '•' }}</span>
              {{ req.label }}
            </li>
          </ul>
        </div>

        <!-- Rol -->
        <div ref="rolDropdownRef" class="relative">
          <label class="text-sm text-gray-600 mb-1 block">Rol *</label>
          <button type="button" @click="rolDropdownOpen = !rolDropdownOpen"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none flex items-center justify-between gap-2 text-left text-sm">
            <span v-if="form.rol" class="inline-flex items-center gap-2">
              <Iconoir :name="roleIcon(form.rol)" class="w-4 h-4" />
              {{ roleLabel(form.rol) }}
            </span>
            <span v-else class="text-gray-400">Seleccionar rol</span>
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="rolDropdownOpen"
            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
            <button v-for="op in rolOptions" :key="op.value" type="button"
              @click="selectRol(op.value)"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-blue-50"
              :class="form.rol === op.value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
              <Iconoir :name="roleIcon(op.value)" class="w-4 h-4" />
              {{ op.label }}
            </button>
          </div>
        </div>

        <!-- Titular (opcional) -->
        <div ref="titularDropdownRef" class="relative">
          <label class="text-sm text-gray-600 mb-1 block">Titular asociado (opcional)</label>
          <button type="button" @click="titularDropdownOpen = !titularDropdownOpen"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none flex items-center justify-between gap-2 text-left text-sm">
            <span v-if="form.titular_id" class="inline-flex items-center gap-2">
              <Iconoir name="identification" class="w-4 h-4 text-gray-400" />
              {{ titularLabel(form.titular_id) }}
            </span>
            <span v-else class="inline-flex items-center gap-2 text-gray-400">
              <Iconoir name="identification" class="w-4 h-4" />
              Sin titular
            </span>
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="titularDropdownOpen"
            class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
            <button type="button" @click="selectTitular(null)"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-blue-50 text-gray-700"
              :class="!form.titular_id ? 'bg-blue-50 text-blue-700 font-medium' : ''">
              Sin titular
            </button>
            <button v-for="t in titulares" :key="t.id" type="button"
              @click="selectTitular(t.id)"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-blue-50"
              :class="form.titular_id === t.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
              {{ t.alias }} — {{ t.nombre }}
            </button>
          </div>
        </div>

        <!-- Activo -->
        <label class="flex items-center gap-2 text-sm text-gray-600">
          <input v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          Activo
        </label>

        <div v-if="formError" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg whitespace-pre-line">{{ formError }}</div>
      </form>
      <template #footer>
        <button @click="submit" :disabled="!formValido || saving"
          class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed transition flex items-center justify-center gap-2">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Crear usuario') }}
        </button>
      </template>
    </AppFormModal>
  </div>
</template>

<script setup>
/**
 * UsuariosView — CRUD de usuarios del sistema.
 * Permite listar, crear y editar usuarios con nombre, email, contraseña, rol,
 * titular asociado y estado activo/inactivo. Incluye toggle de activación,
 * badges de roles y selector de titular.
 */
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useFormatting } from '@/composables/useFormatting'
import { useApiError } from '@/composables/useApiError'
import api from '../../api/axios.js'
import { useUsuariosStore } from '../../stores/usuarios.js'
import AppFormModal from '@/components/common/AppFormModal.vue'
import Iconoir from '../../components/common/Iconoir.vue'

/** Store de usuarios */
const usuarios = useUsuariosStore()
const { formatDate } = useFormatting()
const { parseError } = useApiError()
/** Lista de titulares para el selector del formulario */
const titulares = ref([])
/** Controla visibilidad del modal */
const showForm = ref(false)
/** Indica guardado en curso */
const saving = ref(false)
/** Mensaje de error del formulario */
const formError = ref('')
/** Indica si se está editando un usuario existente */
const editing = ref(false)
/** ID del usuario en edición */
const editId = ref(null)
/** Error de disponibilidad del nombre de usuario */
const nameError = ref('')
/** Confirmación verde de disponibilidad del nombre de usuario */
const nameOk = ref('')
/** Error de disponibilidad del correo */
const emailError = ref('')
/** Confirmación verde de disponibilidad del correo */
const emailOk = ref('')
/** Controla la visibilidad de la contraseña en el formulario */
const mostrarPassword = ref(false)
/** Toast de advertencia (contraseña comprometida) */
const toast = ref({ msg: '' })
let toastTimer = null

/**
 * Muestra un toast de advertencia por unos segundos.
 * @param {string} msg
 */
function mostrarToast(msg) {
  toast.value = { msg }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = { msg: '' } }, 6000)
}

/**
 * Devuelve un índice aleatorio seguro (sin sesgo de módulo) en [0, max).
 * @param {number} max
 * @returns {number}
 */
function indiceAleatorio(max) {
  const limite = Math.floor(0x100000000 / max) * max
  const arr = new Uint32Array(1)
  do {
    crypto.getRandomValues(arr)
  } while (arr[0] >= limite)
  return arr[0] % max
}

/**
 * Genera una contraseña segura aleatoria que cumple los requisitos del
 * formulario y la coloca en el campo (visible para copiarla).
 */
function generarPassword() {
  const mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ'
  const minusculas = 'abcdefghijkmnopqrstuvwxyz'
  const numeros = '23456789'
  const simbolos = '!@#$%&*_+?-='
  const todas = mayusculas + minusculas + numeros + simbolos

  const pass = [
    mayusculas[indiceAleatorio(mayusculas.length)],
    minusculas[indiceAleatorio(minusculas.length)],
    numeros[indiceAleatorio(numeros.length)],
    simbolos[indiceAleatorio(simbolos.length)],
  ]

  for (let i = 0; i < 12; i++) {
    pass.push(todas[indiceAleatorio(todas.length)])
  }

  for (let i = pass.length - 1; i > 0; i--) {
    const j = indiceAleatorio(i + 1)
    ;[pass[i], pass[j]] = [pass[j], pass[i]]
  }

  form.password = pass.join('')
  mostrarPassword.value = true
}

/**
 * Clase CSS del input según si tiene error de disponibilidad.
 * @param {boolean} hasError
 * @returns {string}
 */
const inputClass = (hasError) => (hasError ? 'border-red-400' : '')

/** Datos del formulario */
const form = reactive({
  name: '',
  email: '',
  password: '',
  rol: '',
  titular_id: null,
  activo: true,
})

/**
 * Verifica que un correo tenga formato válido.
 * @param {string} email
 * @returns {boolean}
 */
const esEmailValido = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim())

/**
 * Indica si los campos obligatorios del formulario están completos.
 * La contraseña solo es obligatoria al crear; el titular es opcional.
 * @type {import('vue').ComputedRef<boolean>}
 */
const formValido = computed(() => {
  const camposBase = !!form.name.trim() && esEmailValido(form.email) && !!form.rol
  return editing.value ? camposBase : camposBase && !!form.password
})

/**
 * Requisitos de la contraseña (coinciden con Password::min(8)->mixedCase()->numbers()->symbols()
 * del backend) con estado de cumplimiento en vivo.
 * @type {import('vue').ComputedRef<Array<{label: string, cumplido: boolean}>>}
 */
const passwordRequisitos = computed(() => {
  const p = form.password
  return [
    { label: 'Mínimo 8 caracteres',              cumplido: p.length >= 8 },
    { label: 'Mayúsculas y minúsculas',          cumplido: /[a-z]/.test(p) && /[A-Z]/.test(p) },
    { label: 'Al menos un número',               cumplido: /\d/.test(p) },
    { label: 'Al menos un símbolo',              cumplido: /[^A-Za-z0-9]/.test(p) },
  ]
})

/**
 * Devuelve la clase CSS del badge según el rol.
 * @param {string} rol - Nombre del rol
 * @returns {string}
 */
const roleBadgeClass = (rol) => ({
  'super_admin': 'bg-red-100 text-red-700',
  'admin':       'bg-orange-100 text-orange-700',
  'operador':    'bg-blue-100 text-blue-700',
  'contador':    'bg-green-100 text-green-700',
  'lectura':     'bg-gray-100 text-gray-600',
}[rol] || 'bg-gray-100 text-gray-600')

/**
 * Devuelve el nombre del icono correspondiente al rol.
 * @param {string} rol - Nombre del rol
 * @returns {string}
 */
const roleIcon = (rol) => ({
  'super_admin': 'key',
  'admin':       'shield-check',
  'operador':    'arrows-right-left',
  'contador':    'calculator',
  'lectura':     'eye',
}[rol] || 'users')

/**
 * Etiqueta legible de cada rol (capitalizada y completa).
 * @param {string} rol - Nombre del rol
 * @returns {string}
 */
const roleLabels = {
  'super_admin': 'Super Administrador',
  'admin':       'Administrador',
  'operador':    'Operador',
  'contador':    'Contador',
  'lectura':     'Lectura',
}
const roleLabel = (rol) => roleLabels[rol] || rol

/** Opciones de rol disponibles para crear/editar (sin super_admin) */
const rolOptions = ['admin', 'operador', 'contador', 'lectura']
  .map((value) => ({ value, label: roleLabels[value] }))

/** Controla apertura del dropdown de rol */
const rolDropdownOpen = ref(false)
/** Referencia al contenedor del dropdown para cerrar al hacer clic fuera */
const rolDropdownRef = ref(null)
/** Controla apertura del dropdown de titular */
const titularDropdownOpen = ref(false)
/** Referencia al contenedor del dropdown de titular */
const titularDropdownRef = ref(null)

/**
 * Etiqueta legible de un titular por su id.
 * @param {number|null} id - ID del titular
 * @returns {string}
 */
const titularLabel = (id) => {
  const t = titulares.value.find((x) => x.id === id)
  return t ? `${t.alias} — ${t.nombre}` : ''
}

/** Selecciona un titular y cierra el dropdown */
function selectTitular(id) {
  form.titular_id = id
  titularDropdownOpen.value = false
}

/** Cierra los dropdowns si el clic ocurre fuera de sus contenedores */
function onDocClick(e) {
  const rolEl = rolDropdownRef.value
  const titEl = titularDropdownRef.value
  if (rolEl && !rolEl.contains(e.target)) rolDropdownOpen.value = false
  if (titEl && !titEl.contains(e.target)) titularDropdownOpen.value = false
}

/** Selecciona un rol y cierra el dropdown */
function selectRol(value) {
  form.rol = value
  rolDropdownOpen.value = false
}

/**
 * Indica si un usuario tiene el rol super_admin (no editable desde esta vista).
 * @param {Object} u - Usuario
 * @returns {boolean}
 */
const isSuperAdmin = (u) => Array.isArray(u?.roles) && u.roles.includes('super_admin')

/**
 * Abre el modal en modo creación o edición.
 * @param {Object|null} u - Usuario a editar (null para creación)
 */
function openForm(u = null) {
  rolDropdownOpen.value = false
  titularDropdownOpen.value = false
  nameError.value = ''
  nameOk.value = ''
  emailError.value = ''
  emailOk.value = ''
  if (u) {
    editing.value = true
    editId.value = u.id
    Object.assign(form, {
      name: u.name || '',
      email: u.email || '',
      password: '',
      rol: u.roles?.[0] || '',
      titular_id: u.titular_id ?? null,
      activo: u.activo ?? true,
    })
  } else {
    editing.value = false
    editId.value = null
    Object.assign(form, { name: '', email: '', password: '', rol: '', titular_id: null, activo: true })
  }
  formError.value = ''
  showForm.value = true
}

/** Cierra el modal */
function closeForm() {
  showForm.value = false
}

/**
 * Alterna el estado activo/inactivo de un usuario.
 * @param {Object} u - Usuario a togglear
 * @returns {Promise<void>}
 */
async function handleToggle(u) {
  try {
    await usuarios.toggleActivo(u)
    usuarios.fetchAll()
  } catch (err) {
    alert(parseError(err))
  }
}

/**
 * Verifica contra la API si un nombre de usuario o correo ya está en uso.
 * Si lo está, muestra el error correspondiente junto al campo.
 * @param {'name'|'email'} campo - Campo a verificar
 * @returns {Promise<boolean>} true si está disponible
 */
async function validarDisponible(campo) {
  const valor = (campo === 'name' ? form.name.trim() : form.email.trim()).toLowerCase()
  if (!valor) return true
  if (campo === 'email' && !esEmailValido(valor)) {
    emailError.value = 'Ingresa un correo electrónico válido.'
    emailOk.value = ''
    return false
  }
  try {
    const { data } = await api.get('/usuarios/disponible', {
      params: { campo, valor, exclude_id: editing.value ? editId.value : undefined },
    })
    if (data.disponible) {
      if (campo === 'name') {
        nameError.value = ''
        nameOk.value = 'Nombre de usuario disponible.'
      } else {
        emailError.value = ''
        emailOk.value = 'Correo disponible.'
      }
    } else if (campo === 'name') {
      nameError.value = 'Este nombre de usuario ya está en uso.'
      nameOk.value = ''
    } else {
      emailError.value = 'Este correo ya está en uso.'
      emailOk.value = ''
    }
    return !!data.disponible
  } catch {
    return true
  }
}

/**
 * Envía el formulario para crear o actualizar un usuario.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  if (!form.rol) {
    formError.value = 'Selecciona un rol para el usuario.'
    return
  }
  if (!esEmailValido(form.email)) {
    emailError.value = 'Ingresa un correo electrónico válido.'
    emailOk.value = ''
    return
  }
  const [nameOk, emailOk] = await Promise.all([validarDisponible('name'), validarDisponible('email')])
  if (!nameOk || !emailOk) return
  saving.value = true
  try {
    const body = {
      name: form.name.trim().toLowerCase(),
      email: form.email.trim().toLowerCase(),
      rol: form.rol,
      titular_id: form.titular_id || null,
      activo: form.activo,
    }
    if (form.password) body.password = form.password
    const res = editing.value
      ? await usuarios.update(editId.value, body)
      : await usuarios.create(body)
    if (res?.advertencias?.length) {
      mostrarToast(res.advertencias.join(' '))
    }
    closeForm()
    usuarios.fetchAll()
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/** Carga usuarios y lista de titulares al montar */
onMounted(async () => {
  usuarios.fetchAll()
  try {
    const { data } = await api.get('/titulares')
    titulares.value = Array.isArray(data) ? data : (data.data || [])
  } catch {
    titulares.value = []
  }
  document.addEventListener('click', onDocClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  clearTimeout(toastTimer)
})
</script>
