<template>
  <div class="max-w-3xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">{{ titulo }}</h2>
    </div>

    <div v-if="successRef" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-4">
      <div class="w-8 h-8 border-2 border-green-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-green-700 font-semibold">Solicitud #{{ successRef }} creada</p>
      <p class="text-sm text-gray-500">Redirigiendo a gestión…</p>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <!-- Tipo operación -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Tipo</label>
        <div class="flex gap-3">
          <button type="button" @click="form.tipo = 'compra'"
            class="flex-1 py-3 rounded-xl text-sm font-medium transition border-2"
            :class="form.tipo === 'compra' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'">
            Compra de USD
          </button>
          <button type="button" @click="form.tipo = 'venta'"
            class="flex-1 py-3 rounded-xl text-sm font-medium transition border-2"
            :class="form.tipo === 'venta' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'">
            Venta de USD
          </button>
        </div>
      </div>

      <!-- Fecha -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Fecha</label>
        <input v-model="form.fecha" type="date" :max="today" required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
      </div>

      <!-- Cliente -->
      <ClienteSelector
        :model-value="clienteSeleccionado"
        @update:model-value="clienteSeleccionado = $event"
      />

      <!-- Monto y tasa -->
      <CalculadoraBidireccional
        v-model:monto="form.monto_usd"
        v-model:bolivares="form.bolivares"
        v-model:tasa="form.tasa"
        :tipo="form.tipo"
        :moneda="monedaSel"
        :quote-codigo="quoteCodigo"
        :quote-simbolo="quoteSimbolo"
        :quote-nombre="quoteCodigo === 'VES' ? 'Bolívar' : 'Dólar'"
        :par-str="parStr"
        :tasa-sugerida="tasaSugerida"
        :desfavorable="tasaDesfavorable"
      />

      <!-- Descripción -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm text-gray-600 mb-1">Descripción</label>
        <textarea
          v-model="form.descripcion"
          rows="2"
          placeholder="Notas opcionales"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"
        ></textarea>
      </div>

      <AppErrorState v-if="error" :message="error" :retry="false" />

      <button
        type="submit"
        :disabled="saving || !formularioValido"
        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
      >
        <span v-if="saving" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Creando...' : 'Crear solicitud' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useOperacionForm } from '@/composables/useOperacionForm'
import ClienteSelector from '@/components/clientes/ClienteSelector.vue'
import CalculadoraBidireccional from '@/components/operaciones/CalculadoraBidireccional.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'

const router = useRouter()

const {
  form,
  clienteSeleccionado,
  monedaSel,
  quoteCodigo,
  quoteSimbolo,
  saving,
  error,
  successRef,
  today,
  titulo,
  tasaSugerida,
  tasaDesfavorable,
  parStr,
  formularioValido,
  submit,
  registrarOtra,
  init,
} = useOperacionForm()

onMounted(init)

watch(successRef, (id) => {
  if (id) {
    setTimeout(() => router.push(`/operaciones/${id}/gestionar`), 800)
  }
})
</script>
