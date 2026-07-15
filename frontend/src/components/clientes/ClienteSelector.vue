<template>
  <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
    <h3 class="font-semibold text-gray-700">Cliente</h3>

    <!-- Cliente seleccionado -->
    <div v-if="selectedCliente" class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2">
      <div>
        <span class="text-sm text-blue-700 font-medium">{{ selectedCliente.nombre }}</span>
        <span v-if="selectedCliente.alias" class="text-xs text-blue-500 ml-2">· {{ selectedCliente.alias }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button v-if="!clienteTieneCuentas" type="button" @click="openAddCuentaModal" class="text-xs bg-green-600 text-white px-2 py-1 rounded-lg hover:bg-green-700">+ Cuenta</button>
        <button type="button" @click="clearSelection" class="text-xs text-blue-500 hover:text-blue-700">Cambiar</button>
      </div>
    </div>

    <!-- Búsqueda -->
    <div v-else class="relative">
      <input
        ref="searchInput"
        v-model="search"
        @input="onSearch"
        @keydown="onKeyDown"
        type="text"
        placeholder="Buscar cliente por nombre..."
        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
      />
      <div
        v-if="search && (results.length || !searching)"
        class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto"
      >
        <button
          v-for="(c, i) in results"
          :key="c.id"
          type="button"
          @click="selectCliente(c)"
          class="w-full text-left px-4 py-2.5 text-sm border-b border-gray-100 last:border-0"
          :class="i === activeIndex ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50'"
        >
          <div class="font-medium">{{ c.nombre }}</div>
          <div v-if="c.alias || c.documento" class="text-xs text-gray-400">
            {{ [c.alias, c.documento].filter(Boolean).join(' · ') }}
          </div>
        </button>
        <button
          type="button"
          @click="openCreateModal"
          class="w-full text-left px-4 py-2.5 text-sm text-blue-600 font-medium hover:bg-blue-50 flex items-center gap-1"
          :class="activeIndex === results.length ? 'bg-blue-50' : ''"
        >
          <span>+</span> Crear nuevo cliente "{{ search }}"
        </button>
      </div>
    </div>

    <!-- Modal de creación de cliente + cuentas -->
    <Teleport to="body">
      <AppFormModal v-model="showModal" title="Nuevo cliente">
        <form @submit.prevent="createCliente" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Nombre *</label>
            <input v-model="newCliente.nombre" type="text" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Alias</label>
              <input v-model="newCliente.alias" type="text"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Documento</label>
              <input v-model="newCliente.documento" type="text" placeholder="Cédula / RIF"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Teléfono</label>
              <input v-model="newCliente.telefono" type="text"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
              <input v-model="newCliente.email" type="email"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Notas</label>
            <textarea v-model="newCliente.notas" rows="2"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
          </div>

          <!-- Cuentas del cliente -->
          <div class="border-t border-gray-200 pt-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-semibold text-gray-700 text-sm">Cuentas bancarias</h4>
              <button type="button" @click="addCuenta" class="text-xs bg-blue-600 text-white px-2 py-1 rounded-lg hover:bg-blue-700">+ Agregar cuenta</button>
            </div>

            <div v-if="newCliente.cuentas.length === 0" class="text-xs text-gray-400 py-2">Sin cuentas. Agregá al menos una.</div>

            <div v-for="(cuenta, i) in newCliente.cuentas" :key="i" class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-2 mb-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Cuenta #{{ i + 1 }}</span>
                <button type="button" @click="removeCuenta(i)" class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-[11px] text-gray-500">Banco</label>
                  <select v-model="cuenta.banco_id" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Seleccionar</option>
                    <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nombre }}</option>
                  </select>
                </div>
                <div>
                  <label class="text-[11px] text-gray-500">Moneda</label>
                  <select v-model="cuenta.moneda_id" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Seleccionar</option>
                    <option v-for="m in monedas" :key="m.id" :value="m.id">{{ m.codigo }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-[11px] text-gray-500">Alias *</label>
                  <input v-model="cuenta.alias" type="text" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
                </div>
                <div>
                  <label class="text-[11px] text-gray-500">Tipo</label>
                  <select v-model="cuenta.tipo" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="banco">Banco</option>
                    <option value="zelle">Zelle</option>
                    <option value="wallet">Wallet</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="otro">Otro</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="text-[11px] text-gray-500">Número de cuenta</label>
                <input v-model="cuenta.numero_cuenta" type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
              </div>
            </div>
          </div>

          <AppErrorState v-if="createError" :message="createError" :retry="false" />
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="button" @click="closeModal"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
              Cancelar
            </button>
            <button @click="createCliente" :disabled="creating"
              class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition flex items-center justify-center gap-2">
              <span v-if="creating" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ creating ? 'Creando...' : 'Crear cliente' }}
            </button>
          </div>
        </template>
      </AppFormModal>
    </Teleport>

    <!-- Modal: agregar cuenta a cliente existente -->
    <Teleport to="body">
      <AppFormModal v-model="showCuentaModal" :title="'Agregar cuenta para ' + (selectedCliente?.nombre || '')">
        <form @submit.prevent="addCuentaToCliente" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Banco</label>
            <select v-model="nuevaCuenta.banco_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar</option>
              <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Moneda</label>
            <select v-model="nuevaCuenta.moneda_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar</option>
              <option v-for="m in monedas" :key="m.id" :value="m.id">{{ m.codigo }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Alias *</label>
            <input v-model="nuevaCuenta.alias" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tipo</label>
            <select v-model="nuevaCuenta.tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="banco">Banco</option>
              <option value="zelle">Zelle</option>
              <option value="wallet">Wallet</option>
              <option value="efectivo">Efectivo</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Número de cuenta</label>
            <input v-model="nuevaCuenta.numero_cuenta" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <AppErrorState v-if="cuentaError" :message="cuentaError" :retry="false" />
        </form>
        <template #footer>
          <button @click="addCuentaToCliente" :disabled="savingCuenta" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition">
            {{ savingCuenta ? 'Guardando...' : 'Agregar cuenta' }}
          </button>
        </template>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * Componente selector de cliente con búsqueda y creación inline.
 * Incluye modal para crear clientes y agregar cuentas bancarias.
 *
 * @component
 * @prop {Object} modelValue - Cliente seleccionado { id, nombre, alias, documento }
 * @prop {boolean} clienteTieneCuentas - Indica si el cliente seleccionado tiene cuentas
 * @emit {Object} update:modelValue - Actualiza el cliente seleccionado
 * @emit {string|number} cuenta-agregada - Evento emitido al agregar una cuenta, con el ID del cliente
 */
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useApiError } from '@/composables/useApiError'
import { useClientesStore } from '../../stores/clientes.js'
import { useBancosStore } from '../../stores/bancos.js'
import { useTasasStore } from '../../stores/tasas.js'
import api from '../../api/axios.js'
import AppErrorState from '../common/AppErrorState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

const emit = defineEmits(['update:modelValue', 'cuenta-agregada'])

const props = defineProps({
  /** @type {Object} - Cliente seleccionado */
  modelValue: { type: Object, default: () => ({ id: '', nombre: '' }) },
  /** @type {boolean} - Indica si el cliente tiene cuentas */
  clienteTieneCuentas: { type: Boolean, default: false },
})

const clientesStore = useClientesStore()
const bancosStore = useBancosStore()
const tasasStore = useTasasStore()
const { parseError } = useApiError()

/** @type {import('vue').Ref<string>} - Texto de búsqueda */
const search = ref('')
/** @type {import('vue').Ref<Array<Object>>} - Resultados de la búsqueda */
const results = ref([])
/** @type {import('vue').Ref<boolean>} - Indica si se está realizando una búsqueda */
const searching = ref(false)
/** @type {import('vue').Ref<boolean>} - Controla la visibilidad del modal de creación */
const showModal = ref(false)
/** @type {import('vue').Ref<boolean>} - Indica si se está creando un cliente */
const creating = ref(false)
/** @type {import('vue').Ref<string>} - Mensaje de error en creación */
const createError = ref('')
/** @type {import('vue').Ref<Array<Object>>} - Lista de bancos */
const bancos = ref([])
/** @type {import('vue').Ref<Array<Object>>} - Lista de monedas */
const monedas = ref([])
/** @type {import('vue').Ref<number>} - Índice activo para navegación por teclado */
const activeIndex = ref(-1)
/** @type {import('vue').Ref<HTMLElement|null>} - Ref al input de búsqueda */
const searchInput = ref(null)

/** @type {number|null} - Timeout del debounce de búsqueda */
let debounce = null

/** @type {import('vue').ComputedRef<Object|null>} - Cliente actualmente seleccionado */
const selectedCliente = computed(() =>
  props.modelValue?.id ? props.modelValue : null
)

/** @type {Object} - Datos del formulario de nuevo cliente */
const newCliente = reactive({
  nombre: '',
  alias: '',
  documento: '',
  telefono: '',
  email: '',
  notas: '',
  cuentas: [],
})

/** @type {import('vue').Ref<boolean>} - Controla la visibilidad del modal de agregar cuenta */
const showCuentaModal = ref(false)
/** @type {import('vue').Ref<boolean>} - Indica si se está guardando una cuenta */
const savingCuenta = ref(false)
/** @type {import('vue').Ref<string>} - Mensaje de error al agregar cuenta */
const cuentaError = ref('')
/** @type {Object} - Datos del formulario de nueva cuenta */
const nuevaCuenta = reactive({
  banco_id: '',
  moneda_id: '',
  alias: '',
  tipo: 'banco',
  numero_cuenta: '',
})

/**
 * Retorna un objeto de cuenta vacío para el formulario.
 * @returns {{ banco_id: string, moneda_id: string, alias: string, tipo: string, numero_cuenta: string }}
 */
function emptyCuenta() {
  return { banco_id: '', moneda_id: '', alias: '', tipo: 'banco', numero_cuenta: '' }
}

/**
 * Ejecuta la búsqueda de clientes con debounce de 300ms.
 * @returns {Promise<void>}
 */
function onSearch() {
  clearTimeout(debounce)
  searching.value = true
  activeIndex.value = -1
  debounce = setTimeout(async () => {
    const q = search.value.trim()
    if (!q) { results.value = []; searching.value = false; return }
    try {
      // dentro de onSearch, después de clearTimeout
console.log('Buscando:', q)
  const { data } = await api.get('/clientes', { params: { q } })
  results.value = Array.isArray(data) ? data : (data.data || [])
} catch { results.value = [] }
    finally { searching.value = false }
  }, 300)
}

/**
 * Maneja la navegación por teclado en la lista de resultados.
 * @param {KeyboardEvent} e - Evento de teclado
 * @returns {void}
 */
function onKeyDown(e) {
  if (!results.value.length && search.value.trim()) return

  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      activeIndex.value = Math.min(activeIndex.value + 1, results.value.length - 1)
      break
    case 'ArrowUp':
      e.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, -1)
      break
    case 'Enter':
      e.preventDefault()
      if (activeIndex.value >= 0 && activeIndex.value < results.value.length) {
        selectCliente(results.value[activeIndex.value])
      } else if (search.value.trim()) {
        openCreateModal()
      }
      break
    case 'Escape':
      results.value = []
      activeIndex.value = -1
      break
  }
}

/**
 * Selecciona un cliente y emite el evento update:modelValue.
 * @param {Object} c - Cliente a seleccionar
 * @param {string|number} c.id - ID del cliente
 * @param {string} c.nombre - Nombre del cliente
 * @param {string} [c.alias] - Alias del cliente
 * @param {string} [c.documento] - Documento del cliente
 * @returns {void}
 */
function selectCliente(c) {
  emit('update:modelValue', { id: c.id, nombre: c.nombre, alias: c.alias, documento: c.documento })
  search.value = ''
  results.value = []
  activeIndex.value = -1
}

/**
 * Limpia la selección actual de cliente.
 * @returns {void}
 */
function clearSelection() {
  emit('update:modelValue', { id: '', nombre: '' })
}

/**
 * Abre el modal de creación de cliente con el nombre de búsqueda pre-cargado.
 * @returns {void}
 */
function openCreateModal() {
  newCliente.nombre = search.value.trim()
  newCliente.alias = ''
  newCliente.documento = ''
  newCliente.telefono = ''
  newCliente.email = ''
  newCliente.notas = ''
  newCliente.cuentas = [emptyCuenta()]
  createError.value = ''
  showModal.value = true
}

/**
 * Cierra el modal de creación de cliente.
 * @returns {void}
 */
function closeModal() { showModal.value = false }

/**
 * Agrega una cuenta vacía al formulario de nuevo cliente.
 * @returns {void}
 */
function addCuenta() { newCliente.cuentas.push(emptyCuenta()) }

/**
 * Elimina una cuenta del formulario de nuevo cliente.
 * @param {number} i - Índice de la cuenta a eliminar
 * @returns {void}
 */
function removeCuenta(i) { newCliente.cuentas.splice(i, 1) }

/**
 * Crea un nuevo cliente con sus cuentas asociadas.
 * @returns {Promise<void>}
 */
async function createCliente() {
  if (!newCliente.nombre.trim()) return
  creating.value = true
  createError.value = ''
  try {
    const cliente = await clientesStore.create({
      nombre: newCliente.nombre.trim(),
      alias: newCliente.alias.trim() || undefined,
      documento: newCliente.documento.trim() || undefined,
      telefono: newCliente.telefono.trim() || undefined,
      email: newCliente.email.trim() || undefined,
      notas: newCliente.notas.trim() || undefined,
    })

    for (const c of newCliente.cuentas) {
      if (!c.alias.trim() || !c.banco_id || !c.moneda_id) continue
      await api.post('/cuentas', {
        cliente_id: cliente.id,
        banco_id: Number(c.banco_id),
        moneda_id: Number(c.moneda_id),
        alias: c.alias.trim(),
        tipo: c.tipo || 'banco',
        numero_cuenta: c.numero_cuenta?.trim() || undefined,
        activa: true,
      })
    }

    selectCliente(cliente)
    closeModal()
    emit('cuenta-agregada', cliente.id)
  } catch (err) {
    createError.value = parseError(err)
  } finally {
    creating.value = false
  }
}

/**
 * Abre el modal para agregar una cuenta a un cliente existente.
 * @returns {void}
 */
function openAddCuentaModal() {
  Object.assign(nuevaCuenta, emptyCuenta())
  cuentaError.value = ''
  showCuentaModal.value = true
}

/**
 * Agrega una cuenta bancaria al cliente seleccionado.
 * @returns {Promise<void>}
 */
async function addCuentaToCliente() {
  if (!nuevaCuenta.alias.trim() || !nuevaCuenta.banco_id || !nuevaCuenta.moneda_id) {
    cuentaError.value = 'Banco, moneda y alias son obligatorios.'
    return
  }
  savingCuenta.value = true
  cuentaError.value = ''
  try {
    await api.post('/cuentas', {
      cliente_id: selectedCliente.value.id,
      banco_id: Number(nuevaCuenta.banco_id),
      moneda_id: Number(nuevaCuenta.moneda_id),
      alias: nuevaCuenta.alias.trim(),
      tipo: nuevaCuenta.tipo || 'banco',
      numero_cuenta: nuevaCuenta.numero_cuenta?.trim() || undefined,
      activa: true,
    })
    showCuentaModal.value = false
    emit('cuenta-agregada', selectedCliente.value.id)
  } catch (err) {
    cuentaError.value = parseError(err)
  } finally {
    savingCuenta.value = false
  }
}

onMounted(async () => {
  await bancosStore.fetchAll()
  bancos.value = bancosStore.list
  await tasasStore.fetchMonedas()
  monedas.value = tasasStore.monedas
})
</script>
