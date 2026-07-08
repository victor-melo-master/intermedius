<template>
  <div class="space-y-4">
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
      <span class="text-5xl block mb-4">👤</span>
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
              class="text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="roleBadgeClass(rol)">
              {{ rol }}
            </span>
          </div>
          <p class="text-xs text-gray-500">{{ u.email }}</p>
          <p v-if="u.titular" class="text-xs text-gray-400">Titular: {{ u.titular.alias }}</p>
          <p v-if="u.last_login_at" class="text-xs text-gray-400">Último acceso: {{ formatDate(u.last_login_at) }}</p>
        </div>
        <div class="flex items-center gap-2">
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

    <!-- Modal usuario -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="closeForm">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">{{ editing ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
          <button @click="closeForm" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <!-- Nombre -->
          <input v-model="form.name" required placeholder="Nombre *"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />

          <!-- Email -->
          <input v-model="form.email" type="email" required placeholder="Email *"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />

          <!-- Password -->
          <div>
            <input v-model="form.password" type="password"
              :placeholder="editing ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *'"
              :required="!editing"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
            <p v-if="editing" class="text-xs text-gray-400 mt-1">Dejar en blanco para mantener la contraseña actual.</p>
          </div>

          <!-- Rol -->
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Rol *</label>
            <select v-model="form.rol" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar rol</option>
              <option value="super_admin">super_admin</option>
              <option value="admin">admin</option>
              <option value="operador">operador</option>
              <option value="contador">contador</option>
              <option value="lectura">lectura</option>
            </select>
          </div>

          <!-- Titular (opcional) -->
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Titular asociado (opcional)</label>
            <select v-model="form.titular_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option :value="null">Sin titular</option>
              <option v-for="t in titulares" :key="t.id" :value="t.id">{{ t.alias }} — {{ t.nombre }}</option>
            </select>
          </div>

          <!-- Activo -->
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            Activo
          </label>

          <div v-if="formError" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg whitespace-pre-line">{{ formError }}</div>
          <button type="submit" :disabled="saving"
            class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Crear usuario') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * UsuariosView — CRUD de usuarios del sistema.
 * Permite listar, crear y editar usuarios con nombre, email, contraseña, rol,
 * titular asociado y estado activo/inactivo. Incluye toggle de activación,
 * badges de roles y selector de titular.
 */
import { ref, reactive, onMounted } from 'vue'
import api from '../api/axios.js'
import { useUsuariosStore } from '../stores/usuarios.js'

/** Store de usuarios */
const usuarios = useUsuariosStore()
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
 * Formatea una fecha ISO a dd/mm/aaaa hh:mm.
 * @param {string} d - Fecha ISO
 * @returns {string}
 */
function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

/**
 * Abre el modal en modo creación o edición.
 * @param {Object|null} u - Usuario a editar (null para creación)
 */
function openForm(u = null) {
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
    alert(err.response?.data?.message || err.message)
  }
}

/**
 * Envía el formulario para crear o actualizar un usuario.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = {
      name: form.name,
      email: form.email,
      rol: form.rol,
      titular_id: form.titular_id || null,
      activo: form.activo,
    }
    if (form.password) body.password = form.password
    if (editing.value) {
      await usuarios.update(editId.value, body)
    } else {
      await usuarios.create(body)
    }
    closeForm()
    usuarios.fetchAll()
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      formError.value = Object.values(data.errors).flat().join('\n')
    } else {
      formError.value = data?.message || err.message
    }
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
})
</script>
