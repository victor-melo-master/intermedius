<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <div>
        <h2 class="text-xl font-bold text-gray-800">Verificación #{{ verif.operacion?.id }}</h2>
        <p class="text-sm text-gray-500">{{ verif.operacion?.tipo_operacion?.nombre || 'Operación' }}</p>
      </div>
    </div>

    <!-- Spinner -->
    <div v-if="verif.loading === true" class="flex justify-center py-12">
      <AppLoadingSpinner />
    </div>

    <!-- Error -->
    <AppErrorState
      v-else-if="verif.error && typeof verif.error === 'string'"
      :message="verif.error"
      @retry="cargar"
    />

    <!-- Contenido principal -->
    <template v-else-if="mostrarContenido">
      <!-- Barra de progreso -->
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700">Progreso de verificación</span>
          <span class="text-sm text-gray-500">{{ verif.transaccionesValidadas }}/{{ verif.totalTransacciones }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
          <div class="bg-green-600 h-2.5 rounded-full transition-all duration-300"
            :style="{ width: porcentajeProgreso + '%' }"></div>
        </div>
      </div>

      <!-- Saldos de cuentas -->
      <div v-if="Object.keys(verif.saldos).length" class="bg-white border border-gray-200 rounded-xl p-4 space-y-2">
        <h3 class="font-semibold text-gray-700 text-sm">Saldos de cuentas</h3>
        <div class="grid grid-cols-2 gap-2">
          <div v-for="(saldo, cuentaId) in verif.saldos" :key="cuentaId"
            class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500">{{ saldo.alias }}</p>
            <p class="text-sm font-bold text-gray-800">{{ formatMoney(saldo.saldo, saldo.moneda) }}</p>
          </div>
        </div>
      </div>

      <!-- Transacciones (listado manual, sin componente) -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Transacciones</h3>
          <button @click="showAddModal = true"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
            + Agregar
          </button>
        </div>

        <!-- Mensaje cuando no hay transacciones -->
        <div v-if="!verif.transacciones.length" class="bg-white border border-gray-200 rounded-xl p-6 text-center text-gray-400">
          No hay transacciones
        </div>

        <!-- Listado de transacciones (solo si hay) -->
        <div v-for="tx in verif.transacciones" :key="tx.id"
          class="bg-white border rounded-xl p-4 space-y-3"
          :class="tx.estado === 'validada' ? 'border-green-200 bg-green-50/30' : 'border-gray-200'">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold"
              :class="tx.estado === 'validada' ? 'bg-green-100 text-green-700' :
                       tx.estado === 'rechazada' ? 'bg-red-100 text-red-700' :
                       'bg-yellow-100 text-yellow-700'">
              {{ tx.estado }}
            </span>
            <div class="flex gap-2">
              <button v-if="tx.estado === 'pendiente'" @click="validar(tx)"
                class="text-xs text-green-600 hover:text-green-800 font-medium px-2 py-1 rounded-lg hover:bg-green-50">
                Validar
              </button>
              <button v-if="tx.estado === 'pendiente'" @click="abrirEditar(tx)"
                class="text-xs text-amber-600 hover:text-amber-800 font-medium px-2 py-1 rounded-lg hover:bg-amber-50">
                Editar
              </button>
              <button v-if="tx.estado === 'pendiente'" @click="confirmarEliminar(tx)"
                class="text-xs text-red-600 hover:text-red-800 font-medium px-2 py-1 rounded-lg hover:bg-red-50">
                Eliminar
              </button>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs text-gray-400">Origen</p>
              <p class="text-gray-700">{{ tx.cuenta_origen?.alias || `Cuenta #${tx.cuenta_origen_id}` }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Destino</p>
              <p class="text-gray-700">{{ tx.cuenta_destino?.alias || `Cuenta #${tx.cuenta_destino_id}` }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Monto</p>
              <p class="font-bold text-gray-800">{{ formatMoney(tx.monto, tx.moneda?.codigo) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Moneda</p>
              <p class="text-gray-700">{{ tx.moneda?.codigo }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón cerrar verificación -->
      <div class="pt-2 pb-8">
        <button @click="cerrar" :disabled="!verif.todasValidadas || cerrando"
          class="w-full py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2"
          :class="verif.todasValidadas
            ? 'bg-green-600 hover:bg-green-700 text-white'
            : 'bg-gray-200 text-gray-400 cursor-not-allowed'">
          <span v-if="cerrando" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ cerrando ? 'Cerrando...' : verif.todasValidadas ? 'Cerrar verificación' : 'Todas las transacciones deben estar validadas' }}
        </button>
      </div>
    </template>

    <!-- Fallback -->
    <template v-else-if="verif.loading === false && !verif.operacion && !verif.error">
      <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">
        ⚠️ No se encontraron datos de operación.
      </div>
    </template>

    <!-- Modal: Editar transacción -->
    <Teleport to="body">
      <AppFormModal v-model="showEditModal" title="Editar transacción">
        <form @submit.prevent="guardarEdicion" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta origen</label>
            <select v-model="editForm.cuenta_origen_id" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
              <option v-for="c in cuentasDisponibles" :key="c.id" :value="c.id">
                {{ c.alias }} ({{ c.moneda?.codigo }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta destino</label>
            <select v-model="editForm.cuenta_destino_id" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
              <option v-for="c in cuentasDisponibles" :key="c.id" :value="c.id">
                {{ c.alias }} ({{ c.moneda?.codigo }})
              </option>
            </select>
          </div>
          <AppErrorState v-if="editError" :message="editError" :retry="false" />
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="button" @click="showEditModal = false"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Cancelar</button>
            <button @click="guardarEdicion" :disabled="savingEdit"
              class="flex-1 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-xl hover:bg-amber-600 transition">
              {{ savingEdit ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </template>
      </AppFormModal>
    </Teleport>

    <!-- Modal: Agregar transacción -->
    <Teleport to="body">
      <AppFormModal v-model="showAddModal" title="Agregar transacción">
        <form @submit.prevent="guardarNueva" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta origen</label>
            <select v-model="addForm.cuenta_origen_id" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar...</option>
              <option v-for="c in cuentasDisponibles" :key="c.id" :value="c.id">
                {{ c.alias }} ({{ c.moneda?.codigo }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta destino</label>
            <select v-model="addForm.cuenta_destino_id" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar...</option>
              <option v-for="c in cuentasDisponibles" :key="c.id" :value="c.id">
                {{ c.alias }} ({{ c.moneda?.codigo }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
            <select v-model="addForm.moneda_id" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar...</option>
              <option v-for="m in monedas" :key="m.id" :value="m.id">
                {{ m.codigo }} - {{ m.nombre }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
            <input v-model.number="addForm.monto" type="number" step="0.01" min="0.01" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
          </div>
          <AppErrorState v-if="addError" :message="addError" :retry="false" />
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="button" @click="showAddModal = false"
              class="flex-1 py-2.5 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">Cancelar</button>
            <button @click="guardarNueva" :disabled="savingAdd"
              class="flex-1 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition">
              {{ savingAdd ? 'Agregando...' : 'Agregar' }}
            </button>
          </div>
        </template>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVerificacion } from '@/composables/useVerificacion'
import { useFormatting } from '@/composables/useFormatting'
import { useNotification } from '@/composables/useNotification'
import { useApiError } from '@/composables/useApiError'
import api from '@/api/axios'

import AppLoadingSpinner from '@/components/common/AppLoadingSpinner.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'

const route = useRoute()
const router = useRouter()
const verif = useVerificacion()
const { formatMoney } = useFormatting()
const notify = useNotification()
const { parseError } = useApiError()

// ------------------------------------------------------------------
// Computed
// ------------------------------------------------------------------
const mostrarContenido = computed(() => {
  return verif.loading.value === false && verif.operacion.value !== null
})

const porcentajeProgreso = computed(() => {
  if (!verif.totalTransacciones.value) return 0
  return Math.round((verif.transaccionesValidadas.value / verif.totalTransacciones.value) * 100)
})

// ------------------------------------------------------------------
// Estado
// ------------------------------------------------------------------
const cerrando = ref(false)

const showEditModal = ref(false)
const editForm = ref({ cuenta_origen_id: null, cuenta_destino_id: null })
const editTxId = ref(null)
const editError = ref('')
const savingEdit = ref(false)

const showAddModal = ref(false)
const addForm = ref({ cuenta_origen_id: '', cuenta_destino_id: '', moneda_id: '', monto: '' })
const addError = ref('')
const savingAdd = ref(false)

const cuentasDisponibles = ref([])
const monedas = ref([])

// ------------------------------------------------------------------
// Métodos
// ------------------------------------------------------------------
async function cargar() {
  await verif.fetchVerificacion(route.params.id)
  await cargarCatalogos()
}

async function cargarCatalogos() {
  try {
    const [cuentasRes, monedasRes] = await Promise.all([
      api.get('/cuentas'),
      api.get('/monedas'),
    ])
    cuentasDisponibles.value = Array.isArray(cuentasRes.data) ? cuentasRes.data : cuentasRes.data?.data || []
    monedas.value = Array.isArray(monedasRes.data) ? monedasRes.data : monedasRes.data?.data || []
  } catch (e) {
    console.warn('Error cargando catálogos:', e)
  }
}

function abrirEditar(tx) {
  editTxId.value = tx.id
  editForm.value = {
    cuenta_origen_id: tx.cuenta_origen_id,
    cuenta_destino_id: tx.cuenta_destino_id,
  }
  editError.value = ''
  showEditModal.value = true
}

async function guardarEdicion() {
  savingEdit.value = true
  editError.value = ''
  try {
    await verif.editarTransaccion(route.params.id, editTxId.value, editForm.value)
    showEditModal.value = false
    notify.success('Transacción actualizada')
    await verif.fetchVerificacion(route.params.id)
  } catch (err) {
    editError.value = parseError(err)
  } finally {
    savingEdit.value = false
  }
}

async function guardarNueva() {
  savingAdd.value = true
  addError.value = ''
  try {
    await verif.agregarTransaccion(route.params.id, {
      cuenta_origen_id: addForm.value.cuenta_origen_id,
      cuenta_destino_id: addForm.value.cuenta_destino_id,
      moneda_id: addForm.value.moneda_id,
      monto: addForm.value.monto,
    })
    showAddModal.value = false
    addForm.value = { cuenta_origen_id: '', cuenta_destino_id: '', moneda_id: '', monto: '' }
    notify.success('Transacción agregada')
    await verif.fetchVerificacion(route.params.id)
  } catch (err) {
    addError.value = parseError(err)
  } finally {
    savingAdd.value = false
  }
}

async function validar(tx) {
  try {
    await verif.validarTransaccion(route.params.id, tx.id)
    notify.success('Transacción validada')
    await verif.fetchVerificacion(route.params.id)
  } catch (err) {
    notify.error(parseError(err))
  }
}

async function confirmarEliminar(tx) {
  if (!confirm(`¿Eliminar transacción de ${formatMoney(tx.monto, tx.moneda?.codigo)}?`)) return
  try {
    await verif.eliminarTransaccion(route.params.id, tx.id)
    notify.success('Transacción eliminada')
    await verif.fetchVerificacion(route.params.id)
  } catch (err) {
    notify.error(parseError(err))
  }
}

async function cerrar() {
  cerrando.value = true
  try {
    await verif.cerrarVerificacion(route.params.id)
    notify.success('Verificación completada')
    router.push(`/operaciones/${route.params.id}`)
  } catch (err) {
    notify.error(parseError(err))
  } finally {
    cerrando.value = false
  }
}

// ------------------------------------------------------------------
// Lifecycle
// ------------------------------------------------------------------
onMounted(cargar)
</script>
