<template>
  <div class="rounded-lg px-3 py-2 text-xs space-y-1"
    :class="refStale ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-gray-100 text-gray-500'">
    <template v-if="hayReferencia">
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
        <span v-if="refTasas?.bcv" class="font-semibold">BCV USD: {{ formatVes(refTasas.bcv.tasa) }}</span>
        <span v-if="refTasas?.bcv_eur" class="font-semibold">BCV EUR: {{ formatVes(refTasas.bcv_eur.tasa) }}</span>
        <span v-if="refTasas?.binance_p2p" class="font-semibold">Binance USDT: {{ formatVes(refTasas.binance_p2p.tasa) }}</span>
        <span class="opacity-70">
          <template v-if="refStale"><Iconoir name="exclamation-triangle" class="w-3 h-3 inline text-amber-500" /> Datos desactualizados</template>
          <template v-else>(actualizado {{ refRelativo }})</template>
        </span>
      </div>
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1">
        <template v-if="spreadBcvBinanceNeto !== null">
          <span>BCV USD vs USDT:</span>
          <span class="font-semibold" :class="spreadBcvBinanceNeto >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ formatVes(Math.abs(spreadBcvBinanceNeto)) }} ({{ spreadBcvBinancePorc?.toFixed(1) }}%)
          </span>
        </template>
        <template v-if="spreadBcvEurBinanceNeto !== null">
          <span>BCV EUR vs USDT:</span>
          <span class="font-semibold" :class="spreadBcvEurBinanceNeto >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ formatVes(Math.abs(spreadBcvEurBinanceNeto)) }} ({{ spreadBcvEurBinancePorc?.toFixed(1) }}%)
          </span>
        </template>
        <template v-if="spreadBcvUsdEurNeto !== null">
          <span>BCV USD vs BCV EUR:</span>
          <span class="font-semibold" :class="spreadBcvUsdEurNeto >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ formatVes(Math.abs(spreadBcvUsdEurNeto)) }} ({{ spreadBcvUsdEurPorc?.toFixed(1) }}%)
          </span>
        </template>
      </div>
    </template>
    <span v-else>Tasas de referencia no disponibles</span>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import Iconoir from './Iconoir.vue'
import { useFormatting } from '@/composables/useFormatting'
import { useTasasReferencia } from '@/composables/useTasasReferencia'

const { formatVes } = useFormatting()

const {
  refTasas,
  hayReferencia,
  refStale,
  refRelativo,
  start,
} = useTasasReferencia()

const spreadBcvBinanceNeto = computed(() => {
  const bcv = refTasas.value?.bcv?.tasa
  const binance = refTasas.value?.binance_p2p?.tasa
  if (!bcv || !binance) return null
  return binance - bcv
})

const spreadBcvBinancePorc = computed(() => {
  const bcv = refTasas.value?.bcv?.tasa
  const binance = refTasas.value?.binance_p2p?.tasa
  if (!bcv || !binance) return null
  return ((binance - bcv) / bcv) * 100
})

const spreadBcvEurBinanceNeto = computed(() => {
  const eur = refTasas.value?.bcv_eur?.tasa
  const binance = refTasas.value?.binance_p2p?.tasa
  if (!eur || !binance) return null
  return binance - eur
})

const spreadBcvEurBinancePorc = computed(() => {
  const eur = refTasas.value?.bcv_eur?.tasa
  const binance = refTasas.value?.binance_p2p?.tasa
  if (!eur || !binance) return null
  return ((binance - eur) / eur) * 100
})

const spreadBcvUsdEurNeto = computed(() => {
  const usd = refTasas.value?.bcv?.tasa
  const eur = refTasas.value?.bcv_eur?.tasa
  if (!usd || !eur) return null
  return eur - usd
})

const spreadBcvUsdEurPorc = computed(() => {
  const usd = refTasas.value?.bcv?.tasa
  const eur = refTasas.value?.bcv_eur?.tasa
  if (!usd || !eur) return null
  return ((eur - usd) / usd) * 100
})

onMounted(start)
</script>
