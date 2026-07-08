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
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="bg-white rounded-2xl w-full max-w-lg p-6 relative z-10 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">Nuevo cliente</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
          </div>

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

            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal"
                class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                Cancelar
              </button>
              <button type="submit" :disabled="creating"
                class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition flex items-center justify-center gap-2">
                <span v-if="creating" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                {{ creating ? 'Creando...' : 'Crear cliente' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal: agregar cuenta a cliente existente -->
    <Teleport to="body">
      <div v-if="showCuentaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showCuentaModal = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 relative z-10">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">Agregar cuenta para {{ selectedCliente?.nombre }}</h3>
            <button @click="showCuentaModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
          </div>
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
            <button type="submit" :disabled="savingCuenta" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition">
              {{ savingCuenta ? 'Guardando...' : 'Agregar cuenta' }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useClientesStore } from '../stores/clientes.js'
import { useBancosStore } from '../stores/bancos.js'
import { useTasasStore } from '../stores/tasas.js'
import api from '../api/axios.js'
import AppErrorState from './AppErrorState.vue'

const emit = defineEmits(['update:modelValue', 'cuenta-agregada'])

const props = defineProps({
  modelValue: { type: Object, default: () => ({ id: '', nombre: '' }) },
  clienteTieneCuentas: { type: Boolean, default: false },
})

const clientesStore = useClientesStore()
const bancosStore = useBancosStore()
const tasasStore = useTasasStore()

const search = ref('')
const results = ref([])
const searching = ref(false)
const showModal = ref(false)
const creating = ref(false)
const createError = ref('')
const bancos = ref([])
const monedas = ref([])
const activeIndex = ref(-1)
const searchInput = ref(null)

let debounce = null

const selectedCliente = computed(() =>
  props.modelValue?.id ? props.modelValue : null
)

const newCliente = reactive({
  nombre: '',
  alias: '',
  documento: '',
  telefono: '',
  email: '',
  notas: '',
  cuentas: [],
})

const showCuentaModal = ref(false)
const savingCuenta = ref(false)
const cuentaError = ref('')
const nuevaCuenta = reactive({
  banco_id: '',
  moneda_id: '',
  alias: '',
  tipo: 'banco',
  numero_cuenta: '',
})

function emptyCuenta() {
  return { banco_id: '', moneda_id: '', alias: '', tipo: 'banco', numero_cuenta: '' }
}

// ── Búsqueda ──
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

// ── Navegación con teclado ──
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

function selectCliente(c) {
  emit('update:modelValue', { id: c.id, nombre: c.nombre, alias: c.alias, documento: c.documento })
  search.value = ''
  results.value = []
  activeIndex.value = -1
}

function clearSelection() {
  emit('update:modelValue', { id: '', nombre: '' })
}

// ── Modal crear cliente + cuentas ──
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

function closeModal() { showModal.value = false }

function addCuenta() { newCliente.cuentas.push(emptyCuenta()) }
function removeCuenta(i) { newCliente.cuentas.splice(i, 1) }

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
    const data = err.response?.data
    createError.value = data?.errors
      ? Object.values(data.errors).flat().join('\n')
      : data?.message || err.message
  } finally {
    creating.value = false
  }
}

// ── Agregar cuenta a cliente existente ──
function openAddCuentaModal() {
  Object.assign(nuevaCuenta, emptyCuenta())
  cuentaError.value = ''
  showCuentaModal.value = true
}

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
    const data = err.response?.data
    cuentaError.value = data?.errors
      ? Object.values(data.errors).flat().join('\n')
      : data?.message || err.message
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
