<template>
  <div class="max-w-3xl mx-auto space-y-4 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">{{ titulo }}</h2>
    </div>

    <div v-if="successRef" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center space-y-4">
      <div class="text-4xl">✅</div>
      <p class="text-green-700 font-semibold">Operación registrada {{ successRef }}</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <button @click="registrarOtra" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">Registrar otra</button>
        <button @click="$router.push('/pool')" class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50">Ir al Pool de Pagadores</button>
      </div>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <OperacionFormCabecera
        v-model:tipo="form.tipo"
        v-model:fecha="form.fecha"
        v-model:cliente="clienteSeleccionado"
        :moneda="monedaSel"
        :quote-simbolo="quoteSimbolo"
        :today="today"
        :cliente-tiene-cuentas="clienteTieneCuentas"
        @cuenta-agregada="recargarCuentas"
      />

      <CalculadoraBidireccional
        v-model:monto="form.monto_usd"
        v-model:bolivares="form.bolivares"
        v-model:tasa="form.tasa"
        :tipo="form.tipo"
        :moneda="monedaSel"
        :quote-codigo="quoteCodigo"
        :quote-simbolo="quoteSimbolo"
        :quote-nombre="quoteNombre"
        :par-str="parStr"
        :tasa-sugerida="tasaSugerida"
        :desfavorable="tasaDesfavorable"
      />

      <OperacionFormTransacciones
        :transacciones="form.transacciones"
        :monedas="monedasDelPar"
        :cuentas="cuentas"
        :loading="loadingCuentas"
        :monto-usd="form.monto_usd"
        :monto-ves="form.bolivares"
        :resumen="resumenTransacciones"
        :tipo-operacion="form.tipo"
        :cliente-id="clienteSeleccionado.id || null"
        :intermedius-titular-id="intermediusTitularId"
        :moneda-foreign-id="monedaForeignId"
        :moneda-quote-id="monedaQuoteId"
        @agregar="agregarTransaccion"
        @eliminar="eliminarTransaccion"
        @distribuir="distribuirMontos"
        @limpiar="limpiarTransacciones"
      />

      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm text-gray-600 mb-1">Descripción</label>
        <textarea
          v-model="form.descripcion"
          rows="2"
          placeholder="Notas opcionales"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none resize-none"
        ></textarea>
      </div>

      <OperacionFormResumen :items="resumenItems" />

      <AppErrorState v-if="error" :message="error" :retry="false" />

      <button
        type="submit"
        :disabled="saving || !formularioValido"
        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2"
      >
        <span v-if="saving" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        {{ saving ? 'Registrando...' : 'Registrar operación' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCuentas } from '@/composables/useCuentas'
import { useOperacionForm } from '@/composables/useOperacionForm'

import OperacionFormCabecera from '@/components/operaciones/form/OperacionFormCabecera.vue'
import OperacionFormTransacciones from '@/components/operaciones/form/OperacionFormTransacciones.vue'
import OperacionFormResumen from '@/components/operaciones/form/OperacionFormResumen.vue'
import CalculadoraBidireccional from '@/components/operaciones/CalculadoraBidireccional.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'

const cuentasComposable = useCuentas()
const { loading: loadingCuentas } = cuentasComposable

const {
  form,
  clienteSeleccionado,
  cuentas,
  monedasDelPar,
  saving,
  error,
  successRef,
  intermediusTitularId,
  today,
  monedaSel,
  quoteCodigo,
  quoteSimbolo,
  quoteNombre,
  parStr,
  clienteTieneCuentas,
  titulo,
  tasaSugerida,
  tasaDesfavorable,
  monedaForeignId,
  monedaQuoteId,
  resumenTransacciones,
  formularioValido,
  resumenItems,
  agregarTransaccion,
  eliminarTransaccion,
  limpiarTransacciones,
  distribuirMontos,
  submit,
  registrarOtra,
  recargarCuentas,
  init,
} = useOperacionForm()

onMounted(init)
</script>
