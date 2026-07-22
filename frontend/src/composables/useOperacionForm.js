import { reactive, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useTasas } from './useTasas'
import { useAuth } from './useAuth'
import { useOperaciones } from './useOperaciones'
import { useTitulares } from './useTitulares'
import { useNotification } from './useNotification'
import { useApiError } from './useApiError'
import { useFormatting } from './useFormatting'
import api from '@/api/axios'

export function useOperacionForm() {
  const route = useRoute()
  const tasas = useTasas()
  const auth = useAuth()
  const operaciones = useOperaciones()
  const titulares = useTitulares()
  const notifier = useNotification()
  const { parseError } = useApiError()
  const { formatMoney, roundTo } = useFormatting()

  const monedas = ref([])
  const clienteSeleccionado = ref({ id: '', nombre: '' })
  const saving = ref(false)
  const error = ref('')
  const successRef = ref('')
  const intermediusTitularId = ref(null)
  const today = new Date().toISOString().split('T')[0]

  const editId = computed(() => route.params.id || null)
  const esEdicion = computed(() => !!editId.value)
  const motivoEdicion = computed(() => route.query.motivo || '')
  const monedaSel = computed(() => route.params.moneda || 'USD')
  const quoteCodigo = computed(() => monedaSel.value === 'USD' ? 'VES' : 'USD')
  const parStr = computed(() => `${monedaSel.value}/${quoteCodigo.value}`)
  const quoteSimbolo = computed(() => ({ USD: '$', USDT: '₮', EUR: '€', COP: '$', VES: 'Bs.' })[quoteCodigo.value] || 'Bs.')
  const tipoCodigo = computed(() => (form.tipo === 'venta' ? 'venta_usd' : 'compra_usd'))
  const monedaNombre = computed(() => ({ USD: 'USD', USDT: 'USDT', EUR: 'EUR', COP: 'COP' })[monedaSel.value] || monedaSel.value)

  const textoCompra = computed(() => `La casa compra ${monedaNombre.value}`)
  const textoVenta = computed(() => `La casa vende ${monedaNombre.value}`)

  const tasaPar = computed(() => {
    const vigentes = tasas.vigentes.value || []
    return vigentes.find(t => t.par === parStr.value) || null
  })
  const tasaSugerida = computed(() => {
    if (!tasaPar.value) return null
    return form.tipo === 'venta' ? tasaPar.value.tasa_venta : tasaPar.value.tasa_compra
  })
  const tasaDesfavorable = computed(() => {
    const sug = parseFloat(tasaSugerida.value)
    const t = parseFloat(form.tasa)
    if (!sug || !t) return false
    return form.tipo === 'compra' ? t > sug : t < sug
  })

  const form = reactive({
    tipo: 'compra',
    fecha: today,
    monto_usd: '',
    bolivares: '',
    tasa: '',
    descripcion: '',
  })

  const titulo = computed(() => {
    if (esEdicion.value) return `Editar operación #${editId.value}`
    return `Nueva solicitud — ${monedaSel.value}`
  })

  const formularioValido = computed(() => {
    if (!form.tasa || parseFloat(form.tasa) <= 0) return false
    if (!clienteSeleccionado.value.id) return false
    if (!form.monto_usd || parseFloat(form.monto_usd) <= 0) return false
    if (tasaDesfavorable.value && !form.descripcion.trim()) return false
    return true
  })

  const cargarOperacion = async () => {
    if (!esEdicion.value) return
    await operaciones.fetchOne(editId.value)
    const op = operaciones.detail.value
    if (!op) return

    const codigo = op.tipo_operacion?.codigo
    form.tipo = codigo === 'venta_usd' ? 'venta' : 'compra'
    form.fecha = op.fecha
    form.tasa = parseFloat(op.tasa_aplicada).toFixed(2)

    if (op.cliente) {
      clienteSeleccionado.value = { id: op.cliente.id, nombre: op.cliente.nombre, alias: op.cliente.alias }
    }

    form.descripcion = op.descripcion || ''
    form.monto_usd = op.monto_solicitado ? parseFloat(op.monto_solicitado).toFixed(2) : ''
    form.bolivares = ''
    if (form.monto_usd && form.tasa) {
      form.bolivares = (parseFloat(form.monto_usd) * parseFloat(form.tasa)).toFixed(2)
    }
  }

  const submit = async () => {
    error.value = ''
    saving.value = true

    if (!auth.user.value?.id) {
      error.value = 'Usuario no autenticado. Por favor, inicia sesión nuevamente.'
      saving.value = false
      return
    }

    try {
      const body = {
        fecha: form.fecha,
        tipo_codigo: tipoCodigo.value,
        moneda_codigo: monedaSel.value,
        operador_id: Number(auth.user.value.id),
        tasa_aplicada: roundTo(parseFloat(form.tasa)),
        monto_solicitado: roundTo(parseFloat(form.monto_usd)),
        descripcion: form.descripcion.trim() || null,
      }

      if (clienteSeleccionado.value.id) {
        body.cliente_id = Number(clienteSeleccionado.value.id)
      }

      if (esEdicion.value) {
        body.motivo_edicion = motivoEdicion.value
        await operaciones.update(editId.value, body)
        notifier.success('Operación actualizada')
      } else {
        await operaciones.solicitar(body)
        notifier.success('Solicitud creada')
      }

      const op = operaciones.detail.value
      successRef.value = op?.id?.toString() || ''
    } catch (err) {
      error.value = parseError(err)
      notifier.error(error.value)
    } finally {
      saving.value = false
    }
  }

  const registrarOtra = () => {
    successRef.value = ''
    error.value = ''
    clienteSeleccionado.value = { id: '', nombre: '' }
    Object.assign(form, {
      tipo: 'compra',
      fecha: today,
      monto_usd: '',
      bolivares: '',
      tasa: tasaSugerida.value || '',
      descripcion: '',
    })
    tasas.fetchVigentes()
  }

  const init = async () => {
    await tasas.fetchVigentes()
    await titulares.fetchAll()
    const intermedius = titulares.getIntermedius()
    intermediusTitularId.value = intermedius ? intermedius.id : null

    try {
      const { data: monedasData } = await api.get('/monedas')
      monedas.value = Array.isArray(monedasData) ? monedasData : (monedasData.data || [])
    } catch { monedas.value = [] }

    if (esEdicion.value) {
      await cargarOperacion()
    } else if (tasaSugerida.value) {
      form.tasa = parseFloat(tasaSugerida.value).toFixed(2)
    }
  }

  return {
    form,
    clienteSeleccionado,
    monedas,
    saving,
    error,
    successRef,
    intermediusTitularId,
    today,
    editId,
    esEdicion,
    monedaSel,
    quoteCodigo,
    quoteSimbolo,
    parStr,
    titulo,
    tasaPar,
    tasaSugerida,
    tasaDesfavorable,
    textoCompra,
    textoVenta,
    formularioValido,
    submit,
    registrarOtra,
    init,
  }
}
