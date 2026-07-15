<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Titulares</h2>
      <button @click="openForm" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
        <span>+</span> Nuevo titular
      </button>
    </div>

    <div v-if="titulares.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="titulares.error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ titulares.error }}
      <button @click="titulares.fetchAll()" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="titulares.list.length === 0" class="text-center py-16">
      <span class="text-5xl block mb-4">👤</span>
      <p class="text-gray-500">No hay titulares registrados</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="t in titulares.list" :key="t.id" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-700 font-bold text-sm">
          {{ t.alias?.charAt(0).toUpperCase() || t.nombre?.charAt(0).toUpperCase() || '?' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ t.alias }}</p>
          <p class="text-xs text-gray-500">{{ t.nombre }}</p>
          <p v-if="t.telefono" class="text-xs text-gray-400">{{ t.telefono }}</p>
          <p v-if="t.email" class="text-xs text-gray-400">{{ t.email }}</p>
        </div>
        <div class="text-right">
          <span v-if="t.activo" class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded-full">Activo</span>
          <span v-else class="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full">Inactivo</span>
        </div>
        <button @click="editTitular(t)" class="text-xs text-blue-600 hover:text-blue-800 font-medium px-2 py-1 border border-blue-200 rounded-lg hover:bg-blue-50">Editar</button>
      </div>
    </div>

    <!-- Modal titular -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="closeForm">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">{{ editing ? 'Editar titular' : 'Nuevo titular' }}</h3>
          <button @click="closeForm" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <input v-model="form.nombre" required placeholder="Nombre completo *" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.alias" required placeholder="Alias * (ej: Karol)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.telefono" placeholder="Teléfono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.email" type="email" placeholder="Email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            Activo
          </label>
          <div v-if="formError" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg">{{ formError }}</div>
          <button type="submit" :disabled="saving" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Crear titular') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * TitularesView — CRUD de titulares de cuentas bancarias.
 * Permite listar, crear y editar titulares con nombre, alias, teléfono y email.
 * Modal inline para formulario de creación/edición.
 */
import { ref, reactive, onMounted } from 'vue'
import { useApiError } from '@/composables/useApiError'
import { useTitularesStore } from '../../stores/titulares.js'

/** Store de titulares */
const titulares = useTitularesStore()
const { parseError } = useApiError()
/** Controla visibilidad del modal */
const showForm = ref(false)
/** Indica guardado en curso */
const saving = ref(false)
/** Mensaje de error del formulario */
const formError = ref('')
/** Indica si se está editando un titular existente */
const editing = ref(false)
/** ID del titular en edición */
const editId = ref(null)

/** Datos del formulario */
const form = reactive({
  nombre: '',
  alias: '',
  telefono: '',
  email: '',
  activo: true,
})

/** Abre el modal en modo creación */
function openForm() {
  editing.value = false
  editId.value = null
  Object.assign(form, { nombre: '', alias: '', telefono: '', email: '', activo: true })
  formError.value = ''
  showForm.value = true
}

/**
 * Abre el modal en modo edición con datos precargados.
 * @param {Object} t - Titular a editar
 */
function editTitular(t) {
  editing.value = true
  editId.value = t.id
  Object.assign(form, {
    nombre: t.nombre || '',
    alias: t.alias || '',
    telefono: t.telefono || '',
    email: t.email || '',
    activo: t.activo ?? true,
  })
  formError.value = ''
  showForm.value = true
}

/** Cierra el modal */
function closeForm() {
  showForm.value = false
}

/**
 * Envía el formulario para crear o actualizar un titular.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = {
      nombre: form.nombre,
      alias: form.alias,
      ...(form.telefono ? { telefono: form.telefono } : {}),
      ...(form.email ? { email: form.email } : {}),
      activo: form.activo,
    }
    if (editing.value) {
      await titulares.update(editId.value, body)
    } else {
      await titulares.create(body)
    }
    closeForm()
    titulares.fetchAll()
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/** Carga la lista de titulares al montar */
onMounted(() => titulares.fetchAll())
</script>
