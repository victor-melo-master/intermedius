<template>
  <div class="max-w-3xl mx-auto space-y-4 pb-10">
    <TasasReferencia />

    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition"><Iconoir name="arrow-left" class="w-4 h-4" /> Volver</button>
      <h2 class="text-xl font-bold text-gray-800">{{ titulo }}</h2>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <!-- Tipo operación -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
        <label class="block text-sm font-medium text-gray-600">Tipo</label>
        <div class="flex gap-3">
          <button type="button" @click="form.tipo = 'compra'"
            class="flex-1 py-3 rounded-xl text-sm font-medium transition active:scale-[0.98] border-2"
            :class="form.tipo === 'compra' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'">
            {{ textoCompra }}
            <span class="block text-xs mt-1 opacity-70">El cliente entrega {{ monedaSel }}</span>
          </button>
          <button type="button" @click="form.tipo = 'venta'"
            class="flex-1 py-3 rounded-xl text-sm font-medium transition active:scale-[0.98] border-2"
            :class="form.tipo === 'venta' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'">
            {{ textoVenta }}
            <span class="block text-xs mt-1 opacity-70">La casa entrega {{ monedaSel }}</span>
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

      <!-- Movimientos propuestos (locales) -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">Movimientos propuestos</h3>
          <span class="text-xs text-gray-400">Opcional</span>
        </div>

        <!-- Lista de movimientos agregadas -->
        <div v-if="movimientosLocales.length" class="space-y-1.5">
          <div v-for="(tx, i) in movimientosLocales" :key="i"
            class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 text-sm">
            <span>
              <span class="font-medium">{{ tx._moneda }}</span>
              {{ tx._origen }} → {{ tx._destino }} ·
              {{ formatMoney(tx.monto) }}
            </span>
            <button type="button" @click="eliminarMovimientoLocal(i)"
              class="text-red-500 hover:text-red-700 ml-2"><Iconoir name="x-mark" class="w-4 h-4" /></button>
          </div>
        </div>

        <!-- Formulario para agregar un movimiento local -->
        <div class="space-y-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Moneda</label>
            <select v-model="txForm.moneda_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="">Seleccionar</option>
              <option v-for="m in monedasFiltradas" :key="m.id" :value="m.id">{{ m.codigo }} — {{ m.nombre }}</option>
            </select>
          </div>

          <template v-if="txForm.moneda_id">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">
                  Cuenta origen <span class="text-gray-400">({{ labelOrigen }})</span>
                </label>
                <select v-model="txForm.cuenta_origen_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                  <option value="">Seleccionar</option>
                  <option v-for="c in cuentasOrigen" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">
                  Cuenta destino <span class="text-gray-400">({{ labelDestino }})</span>
                </label>
                <select v-model="txForm.cuenta_destino_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                  <option value="">Seleccionar</option>
                  <option v-for="c in cuentasDestino" :key="c.id" :value="c.id">{{ labelCuenta(c) }}</option>
                </select>
              </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 text-blue-700 text-sm p-3 rounded-lg">
              {{ textoFlujo }}
            </div>

            <div>
              <label class="block text-xs text-gray-500 mb-1">Monto</label>
              <input v-model="txForm.monto" type="number" step="0.01" min="0" placeholder="0.00"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>

            <div>
              <label class="block text-xs text-gray-500 mb-1">Método de pago</label>
              <select v-model="txForm.metodo_pago"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Seleccionar</option>
                <option value="efectivo">Efectivo</option>
                <option value="pago_movil">Pago móvil</option>
                <option value="transferencia">Transferencia</option>
                <option value="zelle">Zelle</option>
                <option value="binance">Binance</option>
                <option value="otro">Otro</option>
              </select>
            </div>

            <div v-if="txForm.metodo_pago && txForm.metodo_pago !== 'efectivo'">
              <label class="block text-xs text-gray-500 mb-1">Comprobante</label>
              <input v-model="txForm.comprobante" placeholder="N° de referencia, voucher, hash..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>
          </template>

          <button type="button" @click="agregarMovimientoLocal" :disabled="!txFormValido"
            class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl disabled:opacity-50 transition active:scale-[0.98]">
            + Agregar movimiento
          </button>
        </div>
      </div>

      <AppErrorState v-if="error" :message="error" :retry="false" />

      <button
        type="submit"
        :disabled="saving || !formularioValido"
        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-semibold py-3 rounded-xl transition active:scale-[0.98] flex items-center justify-center gap-2"
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
import Iconoir from '@/components/common/Iconoir.vue'
import TasasReferencia from '@/components/common/TasasReferencia.vue'

const router = useRouter()

const {
  form,
  clienteSeleccionado,
  monedasFiltradas,
  saving,
  error,
  successRef,
  today,
  titulo,
  monedaSel,
  quoteCodigo,
  quoteSimbolo,
  tasaSugerida,
  tasaDesfavorable,
  parStr,
  textoCompra,
  textoVenta,
  formularioValido,
  movimientosLocales,
  txForm,
  loadingCuentas,
  cuentasOrigen,
  cuentasDestino,
  labelOrigen,
  labelDestino,
  textoFlujo,
  txFormValido,
  labelCuenta,
  agregarMovimientoLocal,
  eliminarMovimientoLocal,
  formatMoney,
  submit,
  init,
} = useOperacionForm()

watch(successRef, (id) => {
  if (id) {
    setTimeout(() => router.push(`/operaciones/${id}/gestionar`), 800)
  }
})

onMounted(init)
</script>
