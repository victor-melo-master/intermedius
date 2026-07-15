<template>
  <div class="max-w-2xl mx-auto space-y-6 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">←</button>
      <h2 class="text-xl font-bold text-gray-800">Seleccionar moneda</h2>
    </div>

    <p class="text-sm text-gray-500">Elige la moneda de la operación:</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <button v-for="m in monedas" :key="m.codigo" @click="seleccionar(m.codigo)"
        class="bg-white border-2 border-gray-200 rounded-2xl p-6 text-left hover:border-blue-500 hover:shadow-md transition active:scale-[0.98]">
        <div class="flex items-center gap-3 mb-2">
          <span class="text-3xl">{{ m.icono }}</span>
          <span class="text-2xl font-bold text-gray-800">{{ m.codigo }}</span>
        </div>
        <p class="text-sm text-gray-500">{{ m.nombre }}</p>
      </button>

      <!-- Botón Intermediada -->
      <router-link to="/operaciones/intermediada/nueva"
        class="bg-white border-2 border-purple-300 rounded-2xl p-6 text-left hover:border-purple-500 hover:shadow-md transition active:scale-[0.98] bg-purple-50">
        <div class="flex items-center gap-3 mb-2">
          <span class="text-3xl">🔗</span>
          <span class="text-2xl font-bold text-purple-700">Intermediada</span>
        </div>
        <p class="text-sm text-purple-600">Conectar comprador y vendedor</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
/**
 * OperacionMonedaView — Pantalla de selección de moneda para nueva operación.
 * Muestra tarjetas con las monedas disponibles (USD, USDT, EUR, COP) y
 * un enlace directo a la operación intermediada.
 */
import { useRouter } from 'vue-router'

/** Router para navegar a la URL de nueva operación con la moneda elegida */
const router = useRouter()

/** Lista de monedas disponibles para operación directa */
const monedas = [
  { codigo: 'USD', nombre: 'Dólar Estadounidense', icono: '💵' },
  { codigo: 'USDT', nombre: 'Tether', icono: '₮' },
  { codigo: 'EUR', nombre: 'Euro', icono: '€' },
  { codigo: 'COP', nombre: 'Peso Colombiano', icono: '$' },
]

/**
 * Navega al formulario de nueva operación con la moneda seleccionada.
 * @param {string} codigo - Código de moneda (USD, USDT, EUR, COP)
 */
function seleccionar(codigo) {
  router.push(`/operaciones/nueva/${codigo}`)
}
</script>
