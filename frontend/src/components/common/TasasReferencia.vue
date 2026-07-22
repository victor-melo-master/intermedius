<template>
  <div class="rounded-lg px-3 py-2 text-xs space-y-1"
    :class="refStale ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-gray-100 text-gray-500'">
    <template v-if="hayReferencia">
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
        <span v-if="refTasas?.bcv" class="font-semibold">BCV USD: {{ formatVes(refTasas.bcv.tasa) }}</span>
        <span v-if="refTasas?.bcv_eur" class="font-semibold">BCV EUR: {{ formatVes(refTasas.bcv_eur.tasa) }}</span>
        <span v-if="refTasas?.binance_p2p" class="font-semibold">Binance USDT: {{ formatVes(refTasas.binance_p2p.tasa) }}</span>
        <span class="opacity-70">
          <template v-if="refStale">⚠️ Datos desactualizados</template>
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
          <span>BCV USD vs EUR:</span>
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useFormatting } from '@/composables/useFormatting'
import api from '@/api/axios'

const { formatVes } = useFormatting()

const refTasas = ref(null)
const ahora = ref(Date.now())

const hayReferencia = computed(() => !!(refTasas.value?.bcv || refTasas.value?.binance_p2p))

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

const refUltimoTs = computed(() => {
  const fechas = [refTasas.value?.bcv?.capturado_en, refTasas.value?.binance_p2p?.capturado_en]
    .filter(Boolean)
    .map(f => new Date(f).getTime())
  return fechas.length ? Math.max(...fechas) : null
})

const refStale = computed(() => {
  if (!refUltimoTs.value) return false
  return (ahora.value - refUltimoTs.value) > 2 * 60 * 1000
})

const refRelativo = computed(() => {
  if (!refUltimoTs.value) return ''
  const diff = Math.max(0, Math.floor((ahora.value - refUltimoTs.value) / 1000))
  if (diff < 60) return 'hace unos segundos'
  const min = Math.floor(diff / 60)
  if (min < 60) return `hace ${min} min`
  const h = Math.floor(min / 60)
  if (h < 24) return `hace ${h} h`
  return `hace ${Math.floor(h / 24)} d`
})

async function fetchTasasReferencia() {
  try {
    const { data } = await api.get('/dashboard/tasas-referencia')
    refTasas.value = data
    ahora.value = Date.now()
  } catch {
    refTasas.value = null
  }
}

let refTimer = null

function scheduleRefresh() {
  refTimer = setTimeout(async () => {
    await fetchTasasReferencia()
    scheduleRefresh()
  }, 1 * 60 * 1000)
}

onMounted(() => {
  fetchTasasReferencia()
  scheduleRefresh()
})

onUnmounted(() => {
  if (refTimer) clearTimeout(refTimer)
})
</script>
