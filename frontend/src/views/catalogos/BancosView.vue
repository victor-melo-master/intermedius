<template>
  <div class="space-y-4">
    <AppPageHeader title="Bancos" :action-label="auth.canWrite ? 'Nuevo banco' : ''" @action="openForm" />

    <AppLoadingSpinner v-if="bancos.loading" />
    <AppErrorState v-else-if="bancos.error" :message="bancos.error" @retry="bancos.fetchAll()" />
    <template v-else-if="bancos.list.length === 0">
      <div class="text-center py-16">
        <Iconoir name="building-library" class="w-12 h-12 mx-auto mb-4 text-ink-muted" />
        <p class="text-ink-muted">No hay bancos registrados</p>
      </div>
    </template>

    <div v-else class="space-y-2">
      <div v-for="b in bancos.list" :key="b.id" class="bg-surface border border-edge rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center text-gold-dark font-bold text-sm">
          {{ b.codigo?.charAt(0) || b.nombre?.charAt(0) || 'B' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ b.nombre }}</p>
          <p class="text-sm text-ink-muted">{{ b.codigo }}</p>
          <p v-if="b.pais" class="text-sm text-ink-muted">País: {{ b.pais }}</p>
        </div>
        <div class="text-right">
          <span v-if="b.activo" class="text-[10px] bg-success-soft text-success px-2 py-0.5 rounded-full">Activo</span>
          <span v-else class="text-[10px] bg-danger-soft text-danger px-2 py-0.5 rounded-full">Inactivo</span>
        </div>
        <button v-if="auth.canWrite" @click="editBanco(b)" class="text-xs text-gold-dark hover:text-gold-dark font-medium px-2 py-1 border border-gold/40 rounded-lg hover:bg-gold-soft">Editar</button>
      </div>
    </div>

    <AppFormModal v-model="showForm" :title="editing ? 'Editar banco' : 'Nuevo banco'" @close="closeForm">
      <form @submit.prevent="submit" class="space-y-3">
        <input v-model="form.nombre" required placeholder="Nombre del banco * (ej: Banesco)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <input v-model="form.codigo" required placeholder="Código * (ej: BANESCO)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <select v-model="form.pais" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
          <option value="VE">Venezuela</option>
          <option value="US">Estados Unidos</option>
          <option value="PA">Panamá</option>
          <option value="ES">España</option>
          <option value="CO">Colombia</option>
          <option value="MX">México</option>
          <option value="PE">Perú</option>
          <option value="CL">Chile</option>
          <option value="AR">Argentina</option>
          <option value="BR">Brasil</option>
          <option value="EC">Ecuador</option>
          <option value="UY">Uruguay</option>
          <option value="DO">República Dominicana</option>
          <option value="CR">Costa Rica</option>
          <option value="GT">Guatemala</option>
          <option value="HN">Honduras</option>
          <option value="SV">El Salvador</option>
          <option value="NI">Nicaragua</option>
          <option value="BO">Bolivia</option>
          <option value="PY">Paraguay</option>
          <option value="PR">Puerto Rico</option>
          <option value="GB">Reino Unido</option>
          <option value="FR">Francia</option>
          <option value="DE">Alemania</option>
          <option value="IT">Italia</option>
          <option value="PT">Portugal</option>
          <option value="CN">China</option>
          <option value="JP">Japón</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-ink-muted">
          <input v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-edge-strong text-gold-dark focus:ring-gold" />
          Activo
        </label>
        <AppErrorState v-if="formError" :message="formError" :retry="false" />
      </form>
      <template #footer>
        <button @click="submit" :disabled="saving" class="w-full bg-gold text-navy font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition flex items-center justify-center gap-2">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Guardando...' : (editing ? 'Guardar cambios' : 'Crear banco') }}
        </button>
      </template>
    </AppFormModal>
  </div>
</template>

<script setup>
/**
 * BancosView — CRUD de bancos.
 * Permite listar, crear y editar bancos con campos nombre, código y país.
 * Modal inline para el formulario de creación/edición.
 */
import { ref, reactive, onMounted } from 'vue'
import { useApiError } from '@/composables/useApiError'
import { useBancosStore } from '../../stores/bancos.js'
import { useAuthStore } from '../../stores/auth.js'
import AppPageHeader from '../../components/common/AppPageHeader.vue'
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'
import AppEmptyState from '../../components/common/AppEmptyState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'
import Iconoir from '../../components/common/Iconoir.vue'

/** Store de bancos */
const bancos = useBancosStore()
/** Store de autenticación para permisos por rol */
const auth = useAuthStore()
const { parseError } = useApiError()
/** Controla visibilidad del modal de formulario */
const showForm = ref(false)
/** Indica si hay una operación de guardado en curso */
const saving = ref(false)
/** Mensaje de error del formulario */
const formError = ref('')
/** Indica si se está editando un banco existente */
const editing = ref(false)
/** ID del banco que se está editando */
const editId = ref(null)

/** Datos del formulario reactivo */
const form = reactive({
  nombre: '',
  codigo: '',
  pais: 'VE',
  activo: true,
})

/**
 * Abre el modal en modo creación.
 * Resetea el formulario a valores por defecto.
 */
function openForm() {
  editing.value = false
  editId.value = null
  Object.assign(form, { nombre: '', codigo: '', pais: 'VE', activo: true })
  formError.value = ''
  showForm.value = true
}

/**
 * Abre el modal en modo edición, precargando los datos del banco.
 * @param {Object} b - Objeto del banco a editar
 */
function editBanco(b) {
  editing.value = true
  editId.value = b.id
  Object.assign(form, {
    nombre: b.nombre || '',
    codigo: b.codigo || '',
    pais: b.pais || 'VE',
    activo: b.activo ?? true,
  })
  formError.value = ''
  showForm.value = true
}

/** Cierra el modal del formulario */
function closeForm() {
  showForm.value = false
}

/**
 * Envía el formulario para crear o actualizar un banco.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = {
      nombre: form.nombre,
      codigo: form.codigo,
      ...(form.pais ? { pais: form.pais } : {}),
      activo: form.activo,
    }
    if (editing.value) {
      await bancos.update(editId.value, body)
    } else {
      await bancos.create(body)
    }
    closeForm()
    bancos.fetchAll()
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/** Carga la lista de bancos al montar el componente */
onMounted(() => bancos.fetchAll())
</script>
