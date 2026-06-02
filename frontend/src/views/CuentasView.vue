<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Cuentas</h2>
      <button @click="openForm" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
        <span>+</span> Nueva cuenta
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl">
      {{ error }}
      <button @click="fetchCuentas" class="underline ml-2">Reintentar</button>
    </div>
    <div v-else-if="cuentas.length === 0" class="text-center py-16">
      <span class="text-5xl block mb-4">🏦</span>
      <p class="text-gray-500">No hay cuentas registradas</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="c in cuentas" :key="c.id" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-lg">🏦</div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.alias || `Cuenta #${c.id}` }}</p>
          <p v-if="c.titular?.alias" class="text-xs text-gray-500">{{ c.titular.alias }} — {{ c.titular.nombre }}</p>
          <p v-if="c.banco?.nombre" class="text-xs text-gray-400">{{ c.banco.nombre }} ({{ c.banco.codigo }})</p>
          <p v-if="c.tipo" class="text-xs text-gray-400 capitalize">{{ c.tipo }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="c.activa" class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded-full">Activa</span>
          <span v-else class="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full">Inactiva</span>
          <div v-if="c.moneda?.codigo" class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">
            {{ c.moneda.codigo }}
          </div>
        </div>
      </div>
    </div>

    <!-- Modal cuenta -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="closeForm">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Nueva cuenta</h3>
          <button @click="closeForm" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <!-- Titular -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="text-sm text-gray-600">Titular</label>
              <button type="button" @click="showTitularInline = !showTitularInline" class="text-xs text-blue-600 hover:text-blue-800 font-medium">+ Nuevo titular</button>
            </div>
            <select v-model="form.titular_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar titular</option>
              <option v-for="t in titulares.list" :key="t.id" :value="t.id">{{ t.alias }}</option>
            </select>
            <!-- Inline crear titular -->
            <div v-if="showTitularInline" class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <p class="text-sm font-medium text-gray-700 mb-2">Crear titular</p>
              <div class="space-y-2">
                <input v-model="titularForm.nombre" placeholder="Nombre completo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
                <input v-model="titularForm.alias" placeholder="Alias" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
                <div class="flex gap-2">
                  <button type="button" @click="submitTitularInline" :disabled="savingInline" class="flex-1 bg-blue-600 text-white text-sm font-medium py-1.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300">Crear</button>
                  <button type="button" @click="showTitularInline = false" class="flex-1 bg-gray-200 text-gray-700 text-sm font-medium py-1.5 rounded-lg hover:bg-gray-300">Cancelar</button>
                </div>
                <p v-if="titularInlineError" class="text-xs text-red-600">{{ titularInlineError }}</p>
              </div>
            </div>
          </div>

          <!-- Banco -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="text-sm text-gray-600">Banco</label>
              <button type="button" @click="showBancoInline = !showBancoInline" class="text-xs text-blue-600 hover:text-blue-800 font-medium">+ Nuevo banco</button>
            </div>
            <select v-model="form.banco_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar banco</option>
              <option v-for="b in bancos.list" :key="b.id" :value="b.id">{{ b.nombre }} ({{ b.codigo }})</option>
            </select>
            <!-- Inline crear banco -->
            <div v-if="showBancoInline" class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <p class="text-sm font-medium text-gray-700 mb-2">Crear banco</p>
              <div class="space-y-2">
                <input v-model="bancoForm.nombre" placeholder="Nombre del banco" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
                <input v-model="bancoForm.codigo" placeholder="Código (ej: BANESCO)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
                <input v-model="bancoForm.pais" placeholder="País (default: VE)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
                <div class="flex gap-2">
                  <button type="button" @click="submitBancoInline" :disabled="savingInline" class="flex-1 bg-blue-600 text-white text-sm font-medium py-1.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300">Crear</button>
                  <button type="button" @click="showBancoInline = false" class="flex-1 bg-gray-200 text-gray-700 text-sm font-medium py-1.5 rounded-lg hover:bg-gray-300">Cancelar</button>
                </div>
                <p v-if="bancoInlineError" class="text-xs text-red-600">{{ bancoInlineError }}</p>
              </div>
            </div>
          </div>

          <!-- Moneda -->
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Moneda</label>
            <select v-model="form.moneda_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar moneda</option>
              <option v-for="m in tasas.monedas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
            </select>
          </div>

          <!-- Alias -->
          <input v-model="form.alias" required placeholder="Alias * (ej: Banesco Karol USD)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />

          <!-- Tipo -->
          <select v-model="form.tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Tipo de cuenta *</option>
            <option value="banco">Banco</option>
            <option value="zelle">Zelle</option>
            <option value="wallet">Wallet</option>
            <option value="efectivo">Efectivo</option>
            <option value="otro">Otro</option>
          </select>

          <!-- Número de cuenta -->
          <input v-model="form.numero_cuenta" placeholder="Número de cuenta (opcional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />

          <!-- Notas -->
          <textarea v-model="form.notas" rows="2" placeholder="Notas (opcional)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>

          <!-- Activa -->
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input v-model="form.activa" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            Activa
          </label>

          <div v-if="formError" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg">{{ formError }}</div>
          <button type="submit" :disabled="saving" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Guardando...' : 'Crear cuenta' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../api/axios.js'
import { useTitularesStore } from '../stores/titulares.js'
import { useBancosStore } from '../stores/bancos.js'
import { useTasasStore } from '../stores/tasas.js'

const titulares = useTitularesStore()
const bancos = useBancosStore()
const tasas = useTasasStore()

const cuentas = ref([])
const loading = ref(false)
const error = ref('')

const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
const savingInline = ref(false)

const showTitularInline = ref(false)
const titularInlineError = ref('')
const titularForm = reactive({ nombre: '', alias: '', telefono: '', email: '', activo: true })

const showBancoInline = ref(false)
const bancoInlineError = ref('')
const bancoForm = reactive({ nombre: '', codigo: '', pais: 'VE', activo: true })

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

async function fetchCuentas() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/cuentas')
    cuentas.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loading.value = false
  }
}

function openForm() {
  Object.assign(form, {
    titular_id: '',
    banco_id: '',
    moneda_id: '',
    alias: '',
    tipo: '',
    numero_cuenta: '',
    notas: '',
    activa: true,
  })
  formError.value = ''
  showTitularInline.value = false
  showBancoInline.value = false
  titularInlineError.value = ''
  bancoInlineError.value = ''
  // Cargar catálogos
  titulares.fetchAll()
  bancos.fetchAll()
  tasas.fetchMonedas()
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

async function submitTitularInline() {
  if (!titularForm.nombre || !titularForm.alias) {
    titularInlineError.value = 'Nombre y alias son obligatorios'
    return
  }
  savingInline.value = true
  titularInlineError.value = ''
  try {
    const body = {
      nombre: titularForm.nombre,
      alias: titularForm.alias,
      activo: true,
    }
    const nuevo = await titulares.create(body)
    await titulares.fetchAll()
    form.titular_id = nuevo.id || titulares.list[titulares.list.length - 1]?.id
    showTitularInline.value = false
    Object.assign(titularForm, { nombre: '', alias: '', telefono: '', email: '', activo: true })
  } catch (err) {
    titularInlineError.value = err.response?.data?.message || err.message
  } finally {
    savingInline.value = false
  }
}

async function submitBancoInline() {
  if (!bancoForm.nombre || !bancoForm.codigo) {
    bancoInlineError.value = 'Nombre y código son obligatorios'
    return
  }
  savingInline.value = true
  bancoInlineError.value = ''
  try {
    const body = {
      nombre: bancoForm.nombre,
      codigo: bancoForm.codigo,
      pais: bancoForm.pais || 'VE',
      activo: true,
    }
    const nuevo = await bancos.create(body)
    await bancos.fetchAll()
    form.banco_id = nuevo.id || bancos.list[bancos.list.length - 1]?.id
    showBancoInline.value = false
    Object.assign(bancoForm, { nombre: '', codigo: '', pais: 'VE', activo: true })
  } catch (err) {
    bancoInlineError.value = err.response?.data?.message || err.message
  } finally {
    savingInline.value = false
  }
}

async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = {
      titular_id: Number(form.titular_id),
      banco_id: Number(form.banco_id),
      moneda_id: Number(form.moneda_id),
      alias: form.alias,
      tipo: form.tipo,
      ...(form.numero_cuenta ? { numero_cuenta: form.numero_cuenta } : {}),
      ...(form.notas ? { notas: form.notas } : {}),
      activa: form.activa,
    }
    await api.post('/cuentas', body)
    closeForm()
    fetchCuentas()
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

onMounted(fetchCuentas)
</script>
