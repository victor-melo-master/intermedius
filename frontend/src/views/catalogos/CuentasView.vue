<template>
  <div class="space-y-4">
    <AppPageHeader title="Cuentas" :action-label="auth.canWrite ? 'Nueva cuenta' : ''" @action="openForm" />

    <AppLoadingSpinner v-if="loading" />
    <AppErrorState v-else-if="error" :message="error" @retry="fetchCuentas" />
    <template v-else-if="cuentas.length === 0">
      <div class="text-center py-16">
        <Iconoir name="building-library" class="w-12 h-12 mx-auto mb-4 text-ink-muted" />
        <p class="text-ink-muted">No hay cuentas registradas</p>
      </div>
    </template>
    <div v-else class="space-y-2">
      <div v-for="c in cuentas" :key="c.id" class="bg-surface border border-edge rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gold-soft flex items-center justify-center"><Iconoir name="building-library" class="w-5 h-5" /></div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.alias || `Cuenta #${c.id}` }}</p>
          <p v-if="c.titular?.alias" class="text-sm text-ink-muted">{{ c.titular.alias }} — {{ c.titular.nombre }}</p>
          <p v-else-if="c.cliente?.nombre" class="text-sm text-ink-muted">Cliente: {{ c.cliente.nombre }}</p>
          <p v-if="c.banco?.nombre" class="text-sm text-ink-muted">{{ c.banco.nombre }} ({{ c.banco.codigo }})</p>
          <p v-if="c.tipo" class="text-sm text-ink-muted capitalize">{{ c.tipo }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="c.activa" class="text-[10px] bg-success-soft text-success px-2 py-0.5 rounded-full">Activa</span>
          <span v-else class="text-[10px] bg-danger-soft text-danger px-2 py-0.5 rounded-full">Inactiva</span>
          <div v-if="c.moneda?.codigo" class="bg-gold-soft text-gold-dark text-xs font-bold px-3 py-1 rounded-full">
            {{ c.moneda.codigo }}
          </div>
          <button
            v-if="auth.canConfig"
            @click="openSaldoModal(c)"
            class="text-xs bg-success-soft text-success-strong px-2 py-1 rounded-lg hover:bg-success-soft"
          >
            <Iconoir name="currency-dollar" class="w-4 h-4" /> Saldo
          </button>
        </div>
      </div>
    </div>

    <AppFormModal v-model="showForm" title="Nueva cuenta" @close="closeForm">
      <form @submit.prevent="submit" class="space-y-3">
        <!-- Cliente (opcional) -->
        <ClienteSelector v-model="clienteSeleccionado" />

        <!-- Tipo de cuenta (siempre primero) -->
        <div>
          <label class="text-sm text-ink-muted mb-1 block">Tipo de cuenta *</label>
          <select v-model="form.tipo" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Seleccionar tipo</option>
            <option value="banco">Banco</option>
            <option value="plataforma">Plataforma</option>
            <option value="cash">Cash</option>
            <option value="zelle">Zelle</option>
            <option value="wallet">Wallet</option>
            <option value="efectivo">Efectivo</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <!-- Titular y Banco (solo si no se seleccionó cliente y el tipo no es efectivo) -->
        <template v-if="!clienteSeleccionado.id">
          <div v-if="form.tipo !== 'efectivo'">
            <div class="flex items-center justify-between mb-1">
              <label class="text-sm text-ink-muted">Titular</label>
              <button type="button" @click="showTitularInline = !showTitularInline" class="text-xs text-gold-dark hover:text-gold-dark font-medium">+ Nuevo titular</button>
            </div>
            <select v-model="form.titular_id" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
              <option value="">Seleccionar titular</option>
              <option v-for="t in titulares.list" :key="t.id" :value="t.id">{{ t.alias }}</option>
            </select>
            <div v-if="showTitularInline" class="mt-2 p-3 bg-surface-soft rounded-lg border border-edge">
              <p class="text-sm font-medium text-ink mb-2">Crear titular</p>
              <div class="space-y-2">
                <input v-model="titularForm.nombre" placeholder="Nombre completo" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <input v-model="titularForm.alias" placeholder="Alias" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <div class="flex gap-2">
                  <button type="button" @click="submitTitularInline" :disabled="savingInline" class="flex-1 bg-gold text-white text-sm font-medium py-1.5 rounded-lg hover:bg-gold-dark disabled:opacity-50">Crear</button>
                  <button type="button" @click="showTitularInline = false" class="flex-1 bg-surface-muted text-ink text-sm font-medium py-1.5 rounded-lg hover:bg-surface-muted">Cancelar</button>
                </div>
                <AppErrorState v-if="titularInlineError" :message="titularInlineError" :retry="false" />
              </div>
            </div>
          </div>

          <div v-if="form.tipo !== 'efectivo'">
            <div class="flex items-center justify-between mb-1">
              <label class="text-sm text-ink-muted">Banco</label>
              <button type="button" @click="showBancoInline = !showBancoInline" class="text-xs text-gold-dark hover:text-gold-dark font-medium">+ Nuevo banco</button>
            </div>
            <select v-model="form.banco_id" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
              <option value="">Seleccionar banco</option>
              <option v-for="b in bancos.list" :key="b.id" :value="b.id">{{ b.nombre }} ({{ b.codigo }})</option>
            </select>
            <div v-if="showBancoInline" class="mt-2 p-3 bg-surface-soft rounded-lg border border-edge">
              <p class="text-sm font-medium text-ink mb-2">Crear banco</p>
              <div class="space-y-2">
                <input v-model="bancoForm.nombre" placeholder="Nombre del banco" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <input v-model="bancoForm.codigo" placeholder="Código (ej: BANESCO)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <input v-model="bancoForm.pais" placeholder="País (default: VE)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <div class="flex gap-2">
                  <button type="button" @click="submitBancoInline" :disabled="savingInline" class="flex-1 bg-gold text-white text-sm font-medium py-1.5 rounded-lg hover:bg-gold-dark disabled:opacity-50">Crear</button>
                  <button type="button" @click="showBancoInline = false" class="flex-1 bg-surface-muted text-ink text-sm font-medium py-1.5 rounded-lg hover:bg-surface-muted">Cancelar</button>
                </div>
                <AppErrorState v-if="bancoInlineError" :message="bancoInlineError" :retry="false" />
              </div>
            </div>
          </div>
        </template>

        <!-- Cuando hay cliente: banco solo si no es efectivo -->
        <template v-if="clienteSeleccionado.id">
          <div v-if="form.tipo !== 'efectivo'">
            <div class="flex items-center justify-between mb-1">
              <label class="text-sm text-ink-muted">Banco</label>
              <button type="button" @click="showBancoInline = !showBancoInline" class="text-xs text-gold-dark hover:text-gold-dark font-medium">+ Nuevo banco</button>
            </div>
            <select v-model="form.banco_id" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
              <option value="">Seleccionar banco</option>
              <option v-for="b in bancos.list" :key="b.id" :value="b.id">{{ b.nombre }} ({{ b.codigo }})</option>
            </select>
            <div v-if="showBancoInline" class="mt-2 p-3 bg-surface-soft rounded-lg border border-edge">
              <p class="text-sm font-medium text-ink mb-2">Crear banco</p>
              <div class="space-y-2">
                <input v-model="bancoForm.nombre" placeholder="Nombre del banco" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <input v-model="bancoForm.codigo" placeholder="Código (ej: BANESCO)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <input v-model="bancoForm.pais" placeholder="País (default: VE)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none text-sm" />
                <div class="flex gap-2">
                  <button type="button" @click="submitBancoInline" :disabled="savingInline" class="flex-1 bg-gold text-white text-sm font-medium py-1.5 rounded-lg hover:bg-gold-dark disabled:opacity-50">Crear</button>
                  <button type="button" @click="showBancoInline = false" class="flex-1 bg-surface-muted text-ink text-sm font-medium py-1.5 rounded-lg hover:bg-surface-muted">Cancelar</button>
                </div>
                <AppErrorState v-if="bancoInlineError" :message="bancoInlineError" :retry="false" />
              </div>
            </div>
          </div>
        </template>

        <!-- Moneda (siempre visible) -->
        <div>
          <label class="text-sm text-ink-muted mb-1 block">Moneda</label>
          <select v-model="form.moneda_id" required class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none">
            <option value="">Seleccionar moneda</option>
            <option v-for="m in tasas.monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
          </select>
        </div>

        <input v-model="form.alias" required placeholder="Alias * (ej: Banesco Karol USD)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />

        <input v-if="form.tipo !== 'efectivo'" v-model="form.numero_cuenta" placeholder="Número de cuenta (opcional)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none" />
        <textarea v-model="form.notas" rows="2" placeholder="Notas (opcional)" class="w-full px-3 py-2 border border-edge-strong rounded-lg focus:ring-2 focus:ring-gold outline-none resize-none"></textarea>

        <label class="flex items-center gap-2 text-sm text-ink-muted">
          <input v-model="form.activa" type="checkbox" class="w-4 h-4 rounded border-edge-strong text-gold-dark focus:ring-gold" />
          Activa
        </label>

        <AppErrorState v-if="formError" :message="formError" :retry="false" />
      </form>
      <template #footer>
        <button @click="submit" :disabled="saving" class="w-full bg-gold text-white font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98] flex items-center justify-center gap-2">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ saving ? 'Guardando...' : 'Crear cuenta' }}
        </button>
      </template>
    </AppFormModal>

    <!-- Modal cargar saldo -->
    <Teleport to="body">
      <AppFormModal v-model="showSaldoModal" title="Cargar saldo">
        <form @submit.prevent="submitSaldo" class="space-y-3">
          <p class="text-sm text-ink-muted">{{ saldoTarget?.alias }} ({{ saldoTarget?.moneda?.codigo }})</p>
          <div>
            <label class="block text-sm text-ink-muted mb-1">Nuevo saldo</label>
            <input v-model="saldoInput" type="number" step="0.01" required class="w-full px-4 py-2.5 border border-edge-strong rounded-xl focus:ring-2 focus:ring-gold outline-none" />
          </div>
          <AppErrorState v-if="saldoError" :message="saldoError" :retry="false" />
        </form>
        <template #footer>
          <button @click="submitSaldo" :disabled="savingSaldo"
            class="w-full bg-gold text-white font-semibold py-2.5 rounded-lg hover:bg-gold-dark disabled:opacity-50 transition active:scale-[0.98]">
            {{ savingSaldo ? 'Guardando...' : 'Guardar saldo' }}
          </button>
        </template>
      </AppFormModal>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * CuentasView — CRUD de cuentas bancarias/plataforma/cash.
 * Permite seleccionar tipo de cuenta primero, ocultar campos de banco/número para efectivo,
 * crear titulares y bancos inline, y cargar saldo a cuentas.
 */
import { ref, reactive, onMounted } from 'vue'
import { useApiError } from '@/composables/useApiError'
import api from '../../api/axios.js'
import { useAuthStore } from '../../stores/auth.js'
import { useTitularesStore } from '../../stores/titulares.js'
import { useBancosStore } from '../../stores/bancos.js'
import { useTasasStore } from '../../stores/tasas.js'
import AppPageHeader from '../../components/common/AppPageHeader.vue'
import AppLoadingSpinner from '../../components/common/AppLoadingSpinner.vue'
import AppErrorState from '../../components/common/AppErrorState.vue'
import AppEmptyState from '../../components/common/AppEmptyState.vue'
import ClienteSelector from '../../components/clientes/ClienteSelector.vue'
import AppFormModal from '@/components/common/AppFormModal.vue'
import Iconoir from '../../components/common/Iconoir.vue'

/** Store de autenticación */
const auth = useAuthStore()
const { parseError } = useApiError()
const { roundTo } = useFormatting()
/** Store de titulares */
const titulares = useTitularesStore()
/** Store de bancos */
const bancos = useBancosStore()
/** Store de tasas (monedas) */
const tasas = useTasasStore()

/** Lista de cuentas cargadas */
const cuentas = ref([])
/** Indica carga de cuentas */
const loading = ref(false)
/** Mensaje de error general */
const error = ref('')

/** Controla visibilidad del modal de formulario */
const showForm = ref(false)
/** Indica guardado en curso */
const saving = ref(false)
/** Mensaje de error del formulario */
const formError = ref('')
/** Indica guardado inline en curso */
const savingInline = ref(false)

/** Controla visibilidad del formulario inline de titular */
const showTitularInline = ref(false)
/** Error del formulario inline de titular */
const titularInlineError = ref('')
/** Datos del formulario inline de titular */
const titularForm = reactive({ nombre: '', alias: '', telefono: '', email: '', activo: true })

/** Controla visibilidad del formulario inline de banco */
const showBancoInline = ref(false)
/** Error del formulario inline de banco */
const bancoInlineError = ref('')
/** Datos del formulario inline de banco */
const bancoForm = reactive({ nombre: '', codigo: '', pais: 'VE', activo: true })

/** Cliente seleccionado opcional para asociar la cuenta */
const clienteSeleccionado = ref({ id: '', nombre: '' })

/** Controla visibilidad del modal de carga de saldo */
const showSaldoModal = ref(false)
/** Cuenta objetivo para cargar saldo */
const saldoTarget = ref(null)
/** Valor ingresado para el nuevo saldo */
const saldoInput = ref('')
/** Error del formulario de saldo */
const saldoError = ref('')
/** Indica guardado de saldo en curso */
const savingSaldo = ref(false)

/** Datos del formulario de cuenta */
const form = reactive({
  titular_id: '',
  banco_id: '',
  moneda_id: '',
  alias: '',
  tipo: '',
  numero_cuenta: '',
  notas: '',
  activa: true,
})

/**
 * Obtiene la lista de cuentas desde la API.
 * @returns {Promise<void>}
 */
async function fetchCuentas() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/cuentas')
    cuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    error.value = parseError(err)
  } finally {
    loading.value = false
  }
}

/** Abre el modal de formulario preparando catálogos y reseteando valores */
function openForm() {
  Object.assign(form, { titular_id: '', banco_id: '', moneda_id: '', alias: '', tipo: '', numero_cuenta: '', notas: '', activa: true })
  formError.value = ''
  showTitularInline.value = false
  showBancoInline.value = false
  titularInlineError.value = ''
  bancoInlineError.value = ''
  clienteSeleccionado.value = { id: '', nombre: '' }
  titulares.fetchAll()
  bancos.fetchAll()
  tasas.fetchMonedas()
  showForm.value = true
}

/** Cierra el modal de formulario */
function closeForm() { showForm.value = false }

/**
 * Crea un titular inline desde el formulario de cuenta.
 * @returns {Promise<void>}
 */
async function submitTitularInline() {
  if (!titularForm.nombre || !titularForm.alias) {
    titularInlineError.value = 'Nombre y alias son obligatorios'
    return
  }
  savingInline.value = true
  titularInlineError.value = ''
  try {
    const body = { nombre: titularForm.nombre, alias: titularForm.alias, activo: true }
    const nuevo = await titulares.create(body)
    await titulares.fetchAll()
    form.titular_id = nuevo.id || titulares.list[titulares.list.length - 1]?.id
    showTitularInline.value = false
    Object.assign(titularForm, { nombre: '', alias: '', telefono: '', email: '', activo: true })
  } catch (err) {
    titularInlineError.value = parseError(err)
  } finally {
    savingInline.value = false
  }
}

/**
 * Crea un banco inline desde el formulario de cuenta.
 * @returns {Promise<void>}
 */
async function submitBancoInline() {
  if (!bancoForm.nombre || !bancoForm.codigo) {
    bancoInlineError.value = 'Nombre y código son obligatorios'
    return
  }
  savingInline.value = true
  bancoInlineError.value = ''
  try {
    const body = { nombre: bancoForm.nombre, codigo: bancoForm.codigo, pais: bancoForm.pais || 'VE', activo: true }
    const nuevo = await bancos.create(body)
    await bancos.fetchAll()
    form.banco_id = nuevo.id || bancos.list[bancos.list.length - 1]?.id
    showBancoInline.value = false
    Object.assign(bancoForm, { nombre: '', codigo: '', pais: 'VE', activo: true })
  } catch (err) {
    bancoInlineError.value = parseError(err)
  } finally {
    savingInline.value = false
  }
}

/**
 * Envía el formulario para crear la cuenta.
 * @returns {Promise<void>}
 */
async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = {
      moneda_id: Number(form.moneda_id),
      alias: form.alias,
      tipo: form.tipo,
      ...(form.numero_cuenta ? { numero_cuenta: form.numero_cuenta } : {}),
      ...(form.notas ? { notas: form.notas } : {}),
      activa: form.activa,
    }

    if (clienteSeleccionado.value.id) {
      body.cliente_id = Number(clienteSeleccionado.value.id)
      if (form.tipo !== 'efectivo') {
        body.banco_id = Number(form.banco_id)
      }
    } else {
      if (form.tipo !== 'efectivo') {
        body.titular_id = Number(form.titular_id)
        body.banco_id = Number(form.banco_id)
      }
    }

    await api.post('/cuentas', body)
    closeForm()
    fetchCuentas()
  } catch (err) {
    formError.value = parseError(err)
  } finally {
    saving.value = false
  }
}

/**
 * Abre el modal para cargar saldo de una cuenta.
 * @param {Object} cuenta - Cuenta a actualizar saldo
 */
function openSaldoModal(cuenta) {
  saldoTarget.value = cuenta
  saldoInput.value = cuenta.saldo_cache || ''
  saldoError.value = ''
  showSaldoModal.value = true
}

/**
 * Envía el nuevo saldo para la cuenta seleccionada.
 * @returns {Promise<void>}
 */
async function submitSaldo() {
  if (!saldoInput.value) return
  savingSaldo.value = true
  saldoError.value = ''
  try {
    await api.post(`/cuentas/${saldoTarget.value.id}/saldo`, { saldo: roundTo(parseFloat(saldoInput.value)) })
    showSaldoModal.value = false
    await fetchCuentas()
  } catch (err) {
    saldoError.value = parseError(err)
  } finally {
    savingSaldo.value = false
  }
}

/** Carga la lista de cuentas al montar el componente */
onMounted(fetchCuentas)
</script>
