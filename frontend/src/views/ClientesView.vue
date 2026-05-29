<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-xl font-bold text-gray-800">Clientes</h2>
      <button @click="showForm = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
        <span>+</span> Nuevo cliente
      </button>
    </div>

    <div class="relative">
      <input v-model="search" @input="debounceSearch" placeholder="Buscar por nombre o alias..."
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
      <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
      <button v-if="search" @click="search = ''; clientes.fetchAll()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">✕</button>
    </div>

    <div v-if="clientes.loading" class="text-center py-12">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
    </div>
    <div v-else-if="clientes.error" class="bg-red-50 text-red-600 p-4 rounded-xl">{{ clientes.error }}</div>
    <div v-else-if="clientes.list.length === 0" class="text-center py-16">
      <span class="text-5xl block mb-4">👥</span>
      <p class="text-gray-500">{{ search ? 'Sin resultados' : 'No hay clientes' }}</p>
    </div>
    <div v-else class="space-y-2">
      <div v-for="c in clientes.list" :key="c.id" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-700 font-bold text-sm">{{ c.nombre.charAt(0).toUpperCase() }}</div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm truncate">{{ c.nombre }}</p>
          <p v-if="c.alias" class="text-xs text-gray-500 truncate">{{ c.alias }}</p>
          <p v-if="c.telefono" class="text-xs text-gray-400">{{ c.telefono }}</p>
        </div>
        <div class="text-right">
          <p class="text-sm font-bold" :class="(c.saldo_cache_usd || 0) >= 0 ? 'text-green-600' : 'text-red-600'">${{ format(c.saldo_cache_usd) }}</p>
          <span v-if="!c.activo" class="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full">Inactivo</span>
        </div>
      </div>
    </div>

    <!-- Modal nuevo cliente -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="showForm = false">
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-md p-6 relative z-10">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-lg">Nuevo cliente</h3>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <input v-model="form.nombre" required placeholder="Nombre *" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.alias" placeholder="Alias" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.telefono" placeholder="Teléfono" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <input v-model="form.email" type="email" placeholder="Email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
          <textarea v-model="form.notas" rows="2" placeholder="Notas" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
          <div v-if="formError" class="bg-red-50 text-red-600 text-sm p-3 rounded-lg">{{ formError }}</div>
          <button type="submit" :disabled="saving" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 disabled:bg-blue-300 transition flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ saving ? 'Creando...' : 'Crear cliente' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useClientesStore } from '../stores/clientes.js'

const clientes = useClientesStore()
const search = ref('')
const showForm = ref(false)
const saving = ref(false)
const formError = ref('')
let debounce = null

const form = reactive({ nombre: '', alias: '', telefono: '', email: '', notas: '' })

function debounceSearch() {
  clearTimeout(debounce)
  debounce = setTimeout(() => clientes.fetchAll(search.value), 400)
}

function format(n) {
  return new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0)
}

async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const body = { nombre: form.nombre }
    if (form.alias) body.alias = form.alias
    if (form.telefono) body.telefono = form.telefono
    if (form.email) body.email = form.email
    if (form.notas) body.notas = form.notas
    await clientes.create(body)
    showForm.value = false
    clientes.fetchAll(search.value)
    Object.assign(form, { nombre: '', alias: '', telefono: '', email: '', notas: '' })
  } catch (err) {
    formError.value = err.response?.data?.message || err.message
  } finally {
    saving.value = false
  }
}

onMounted(() => clientes.fetchAll())
</script>
