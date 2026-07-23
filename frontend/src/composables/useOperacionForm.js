import { reactive, ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useTasasReferencia } from './useTasasReferencia'
import { useAuth } from './useAuth'
import { useOperaciones } from './useOperaciones'
import { useTitulares } from './useTitulares'
import { useNotification } from './useNotification'
import { useApiError } from './useApiError'
import { useFormatting } from './useFormatting'
import { useSaldoCuenta } from './useSaldoCuenta'
import { useMetodoPago } from './useMetodoPago'
import api from '@/api/axios'

export function useOperacionForm() {
  const route = useRoute()
  const tasasRef = useTasasReferencia()
  const auth = useAuth()
  const operaciones = useOperaciones()
  const titulares = useTitulares()
  const notifier = useNotification()
  const { parseError } = useApiError()
  const { formatMoney, roundTo } = useFormatting()
  const saldoCuenta = useSaldoCuenta()
  const metodoPagoUtil = useMetodoPago()

  const monedas = ref([])
  const clienteSeleccionado = ref({ id: '', nombre: '' })
  const saving = ref(false)
  const error = ref('')
  const successRef = ref('')
  const intermediusTitularId = ref(null)
  const today = new Date().toISOString().split('T')[0]

  const movimientosLocales = ref([])
  const cuentasCliente = ref([])
  const cuentasIntermedius = ref([])
  const loadingCuentas = ref(false)

  const txForm = reactive({
    moneda_id: '',
    cuenta_origen_id: '',
    cuenta_destino_id: '',
    monto: '',
    metodo_pago: '',
    comprobante: '',
  })

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

  const tasaSugerida = computed(() => {
    const ref = tasasRef.refTasaPorMoneda(monedaSel.value)
    return ref ? parseFloat(ref).toFixed(2) : null
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
    return true
  })

  const monedasFiltradas = computed(() =>
    monedas.value.filter(m => [monedaSel.value, 'VES'].includes(m.codigo))
  )

  const monedaSelObj = computed(() =>
    monedas.value.find(m => m.id == txForm.moneda_id) || null
  )

  const esDivisa = computed(() => monedaSelObj.value?.codigo !== 'VES')

  const cuentasOrigen = computed(() => {
    if (!txForm.moneda_id) return []
    const inter = cuentasIntermedius.value.filter(c => c.moneda_id == txForm.moneda_id)
    const cli = cuentasCliente.value.filter(c => c.moneda_id == txForm.moneda_id)
    if (form.tipo === 'compra') return esDivisa.value ? cli : inter
    return esDivisa.value ? inter : cli
  })

  const cuentasDestino = computed(() => {
    if (!txForm.moneda_id) return []
    const inter = cuentasIntermedius.value.filter(c => c.moneda_id == txForm.moneda_id)
    const cli = cuentasCliente.value.filter(c => c.moneda_id == txForm.moneda_id)
    if (form.tipo === 'compra') return esDivisa.value ? inter : cli
    return esDivisa.value ? cli : inter
  })

  const labelOrigen = computed(() => {
    if (!cuentasOrigen.value.length) return ''
    return cuentasOrigen.value[0]?.titular_id ? 'Intermedius' : (clienteSeleccionado.value.nombre || 'Cliente')
  })

  const labelDestino = computed(() => {
    if (!cuentasDestino.value.length) return ''
    return cuentasDestino.value[0]?.titular_id ? 'Intermedius' : (clienteSeleccionado.value.nombre || 'Cliente')
  })

  const textoFlujo = computed(() => {
    if (!monedaSelObj.value) return ''
    const moneda = monedaSelObj.value.codigo
    const nombre = clienteSeleccionado.value.nombre || 'Cliente'
    if (form.tipo === 'compra') {
      return esDivisa.value
        ? `Compra: ${nombre} entrega ${moneda} a Intermedius`
        : `Compra: Intermedius entrega ${moneda} a ${nombre}`
    }
    return esDivisa.value
      ? `Venta: Intermedius entrega ${moneda} a ${nombre}`
      : `Venta: ${nombre} entrega ${moneda} a Intermedius`
  })

  const txFormValido = computed(() =>
    txForm.moneda_id && txForm.cuenta_origen_id && txForm.cuenta_destino_id && parseFloat(txForm.monto) > 0
  )

  function labelCuenta(c) {
    const tipo = c.banco?.nombre || c.tipo || 'cuenta'
    let saldo = ''
    if (c.titular_id && c.saldo_cache != null) {
      saldo = ` · Saldo: ${c.moneda?.simbolo || ''}${parseFloat(c.saldo_cache).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`
    }
    return `${c.alias} · ${tipo}${saldo}`
  }

  function agregarMovimientoLocal() {
    if (!txFormValido.value) return
    movimientosLocales.value.push({
      cuenta_origen_id: Number(txForm.cuenta_origen_id),
      cuenta_destino_id: Number(txForm.cuenta_destino_id),
      moneda_id: Number(txForm.moneda_id),
      monto: parseFloat(txForm.monto),
      tasa_aplicada: roundTo(parseFloat(form.tasa)),
      metodo_pago: txForm.metodo_pago || 'transferencia',
      comprobante: txForm.comprobante || null,
      _origen: cuentasOrigen.value.find(c => c.id == txForm.cuenta_origen_id)?.alias || '',
      _destino: cuentasDestino.value.find(c => c.id == txForm.cuenta_destino_id)?.alias || '',
      _moneda: monedaSelObj.value?.codigo || '',
    })
    txForm.cuenta_origen_id = ''
    txForm.cuenta_destino_id = ''
    txForm.monto = ''
    txForm.metodo_pago = ''
    txForm.comprobante = ''
  }

  function eliminarMovimientoLocal(index) {
    movimientosLocales.value.splice(index, 1)
  }

  async function cargarCuentas() {
    loadingCuentas.value = true
    try {
      if (intermediusTitularId.value) {
        const { data } = await api.get(`/cuentas?titular_id=${intermediusTitularId.value}`)
        cuentasIntermedius.value = Array.isArray(data) ? data : (data.data || [])
      }
      if (clienteSeleccionado.value.id) {
        const { data } = await api.get(`/cuentas?cliente_id=${clienteSeleccionado.value.id}`)
        cuentasCliente.value = Array.isArray(data) ? data : (data.data || [])
      }
    } catch {
      cuentasIntermedius.value = []
      cuentasCliente.value = []
    }
    loadingCuentas.value = false
  }

  const cuentaOrigenObj = computed(() =>
    cuentasOrigen.value.find(c => c.id == txForm.cuenta_origen_id) || null
  )

  const cuentaDestinoObj = computed(() =>
    cuentasDestino.value.find(c => c.id == txForm.cuenta_destino_id) || null
  )

  watch(() => [clienteSeleccionado.value.id, intermediusTitularId.value], cargarCuentas)

  watch(() => txForm.moneda_id, () => {
    txForm.cuenta_origen_id = ''
    txForm.cuenta_destino_id = ''
  })

  watch(cuentaOrigenObj, async (cuenta) => {
    if (cuenta?.cliente_id && esDivisa.value && form.tipo === 'compra' && !txForm.monto) {
      const saldo = await saldoCuenta.getSaldo(cuenta.id)
      if (saldo > 0) txForm.monto = String(saldo)
    }
    autoDetectarMetodoPago()
  })

  watch(cuentaDestinoObj, () => {
    autoDetectarMetodoPago()
  })

  function autoDetectarMetodoPago() {
    const origen = cuentaOrigenObj.value
    const destino = cuentaDestinoObj.value
    if (!origen || !destino) return
    const detectado = metodoPagoUtil.detectar(origen, destino)
    if (detectado && !txForm.metodo_pago) {
      txForm.metodo_pago = detectado
    }
  }

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

      if (movimientosLocales.value.length > 0) {
        body.transacciones = movimientosLocales.value.map(tx => ({
          cuenta_origen_id: tx.cuenta_origen_id,
          cuenta_destino_id: tx.cuenta_destino_id,
          moneda_id: tx.moneda_id,
          monto: tx.monto,
          tasa_aplicada: tx.tasa_aplicada,
          metodo_pago: tx.metodo_pago,
          comprobante: tx.comprobante,
        }))
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
    movimientosLocales.value = []
    tasasRef.fetch()
  }

  const init = async () => {
    await tasasRef.fetch()
    await titulares.fetchAll()
    const intermedius = titulares.getIntermedius()
    intermediusTitularId.value = intermedius ? intermedius.id : null

    try {
      const { data: monedasData } = await api.get('/monedas')
      monedas.value = Array.isArray(monedasData) ? monedasData : (monedasData.data || [])
    } catch { monedas.value = [] }

    await cargarCuentas()

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
    monedasFiltradas,
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
    tasaSugerida,
    tasaDesfavorable,
    textoCompra,
    textoVenta,
    formularioValido,
    movimientosLocales,
    txForm,
    cuentasCliente,
    cuentasIntermedius,
    loadingCuentas,
    monedaSelObj,
    esDivisa,
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
    registrarOtra,
    init,
  }
}
