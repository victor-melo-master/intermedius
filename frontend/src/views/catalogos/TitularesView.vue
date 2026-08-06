<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-heading">Titulares</h2>
      <button v-if="auth.canWrite" @click="openForm" class="px-4 py-2 bg-gold text-navy rounded-lg text-sm font-medium hover:bg-gold-dark flex items-center gap-1">
        <span>+</span> Nuevo titular
      </button>
    </div>

    <div v-if="titulares.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-gold border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="titulares.error" class="bg-danger-soft text-danger p-4 rounded-xl">
      {{ titulares.error }}
      <button @click="titulares.fetchAll()" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="titulares.list.length === 0" class="text-center py-16">
      <Iconoir name="users-solid" class="w-12 h-12 mx-auto mb-4 text-ink-faint" />
      <p class="text-ink-soft">No hay titulares registrados</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="t in titulares.list" :key="t.id" class="bg-surface border border-edge rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-sm">
          {{ t.alias?.charAt(0).toUpperCase() || t.nombre?.charAt(0).toUpperCase() || '?' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ t.alias }}</p>
          <p class="text-xs text-ink-soft">{{ t.nombre }}</p>
          <p v-if="t.telefono" class="text-xs text-ink-faint">{{ t.telefono }}</p>
          <p v-if="t.email" class="text-xs text-ink-faint">{{ t.email }}</p>
        </div>
        <div class="text-right">
          <span v-if="t.activo" class="text-[10px] bg-success-soft text-success px-2 py-0.5 rounded-full">Activo</span>
          <span v-else class="text-[10px] bg-danger-soft text-danger px-2 py-0.5 rounded-full">Inactivo</span>
        </div>
        <button v-if="auth.canWrite" @click="editTitular(t)" class="text-xs text-gold-dark hover:text-gold-dark font-medium px-2 py-1 border border-gold/40 rounded-lg hover:bg-gold-soft">Editar</button>
      </div>
    </div>

    <AppFormModal v-model="showForm" :title="editing ? 'Editar titular' : 'Nuevo titular'" @close="closeForm">
      <form @submit.prevent="submit" class="space-y-3">
        <input v-model="form.nombre" required placeholder="Nombre completo *" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.alias" required placeholder="Alias * (ej: Karol)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.telefono" placeholder="Teléfono" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.email" type="email" placeholder="Email" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <label class="flex items-center gap-2 text-sm text-ink-muted">
          <input v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-edge-strong text-gold-dark focus:ring-gold" />
          Activo
        </label>
        <div v-if="formError" class="bg-danger-soft text-danger text-sm p-3 rounded-lg">{{ formError }}</div>
      </form>
      <template #footer>
        <button @click="submit" :disabled="saving" class="w-full bg-gold text-navy font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition flex items-center justify-center gap-2">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Crear titular') }}
        </button>
      </template>
    </AppFormModal>
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
import { useAuthStore } from '../../stores/auth.js'
import AppFormModal from '@/components/common/AppFormModal.vue'
import Iconoir from '../../components/common/Iconoir.vue'

/** Store de titulares */
const titulares = useTitularesStore()
/** Store de autenticación para permisos por rol */
const auth = useAuthStore()
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
