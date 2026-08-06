<template>
  <div class="max-w-2xl mx-auto space-y-6 pb-10">
    <div class="flex items-center gap-3 mb-2">
      <button @click="$router.back()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted rounded-lg transition"><Iconoir name="arrow-left" class="w-4 h-4" /> Volver</button>
      <h2 class="text-xl font-bold text-heading">Seleccionar moneda</h2>
    </div>

    <p class="text-sm text-ink-soft">Elige la moneda de la operación:</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <button v-for="m in monedas" :key="m.codigo" @click="seleccionar(m.codigo)"
        class="bg-surface border-2 border-edge rounded-2xl p-6 text-left hover:border-info hover:shadow-md transition active:scale-[0.98]">
        <div class="flex items-center gap-3 mb-2">
          <span class="text-3xl">{{ m.icono }}</span>
          <span class="text-2xl font-bold text-heading">{{ m.codigo }}</span>
        </div>
        <p class="text-sm text-ink-soft">{{ m.nombre }}</p>
      </button>

      <!-- Botón Intermediada -->
      <router-link to="/operaciones/intermediada/nueva"
        class="bg-surface border-2 border-violet-edge rounded-2xl p-6 text-left hover:border-violet hover:shadow-md transition active:scale-[0.98] bg-violet-soft">
        <div class="flex items-center gap-3 mb-2">
          <Iconoir name="link" class="w-8 h-8" />
          <span class="text-2xl font-bold text-violet-strong">Intermediada</span>
        </div>
        <p class="text-sm text-violet">Conectar comprador y vendedor</p>
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
import Iconoir from '@/components/common/Iconoir.vue'
import { useRouter } from 'vue-router'

/** Router para navegar a la URL de nueva operación con la moneda elegida */
const router = useRouter()

/** Lista de monedas disponibles para operación directa */
const monedas = [
  { codigo: 'USD', nombre: 'Dólar Estadounidense', icono: '$' },
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
