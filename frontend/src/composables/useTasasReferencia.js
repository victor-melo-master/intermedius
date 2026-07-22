import { ref, computed, onUnmounted } from 'vue'
import api from '@/api/axios'

export function useTasasReferencia() {
  const refTasas = ref(null)
  const ahora = ref(Date.now())
  const loading = ref(false)

  const hayReferencia = computed(() => !!(refTasas.value?.bcv || refTasas.value?.binance_p2p))

  function refTasaPorMoneda(codigo) {
    if (!refTasas.value) return null
    const map = { USD: 'bcv', EUR: 'bcv_eur', USDT: 'binance_p2p' }
    const key = map[codigo]
    return key ? refTasas.value[key]?.tasa : null
  }

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

  async function fetch() {
    loading.value = true
    try {
      const { data } = await api.get('/dashboard/tasas-referencia')
      refTasas.value = data
      ahora.value = Date.now()
    } catch {
      refTasas.value = null
    } finally {
      loading.value = false
    }
  }

  let timer = null

  function start() {
    fetch()
    scheduleRefresh()
  }

  function scheduleRefresh() {
    timer = setTimeout(async () => {
      await fetch()
      scheduleRefresh()
    }, 1 * 60 * 1000)
  }

  function stop() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  return {
    refTasas,
    ahora,
    loading,
    hayReferencia,
    refTasaPorMoneda,
    refUltimoTs,
    refStale,
    refRelativo,
    fetch,
    start,
    stop,
  }
}
