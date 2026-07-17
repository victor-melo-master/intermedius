import { reactive, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useTasas } from './useTasas'
import { useAuth } from './useAuth'
import { useOperaciones } from './useOperaciones'
import { useCuentas } from './useCuentas'
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
  const cuentasComposable = useCuentas()
  const titulares = useTitulares()
  const notifier = useNotification()
  const { parseError } = useApiError()
  const { formatMoney, roundTo } = useFormatting()

  const monedas = ref([])
  const cuentas = ref([])
  const clienteSeleccionado = ref({ id: '', nombre: '' })
  const saving = ref(false)
  const error = ref('')
  const successRef = ref('')
  const intermediusTitularId = ref(null)
  const today = new Date().toISOString().split('T')[0]

  const SIMBOLOS = { USD: '$', USDT: '₮', EUR: '€', COP: '$', VES: 'Bs.' }
  const NOMBRES = { USD: 'Dólar', USDT: 'Tether', EUR: 'Euro', COP: 'Peso', VES: 'Bolívar' }

  const editId = computed(() => route.params.id || null)
  const esEdicion = computed(() => !!editId.value)
  const motivoEdicion = computed(() => route.query.motivo || '')
  const monedaSel = computed(() => route.params.moneda || 'USD')
  const quoteCodigo = computed(() => monedaSel.value === 'USD' ? 'VES' : 'USD')
  const parStr = computed(() => `${monedaSel.value}/${quoteCodigo.value}`)
  const quoteSimbolo = computed(() => SIMBOLOS[quoteCodigo.value] || 'Bs.')
  const quoteNombre = computed(() => NOMBRES[quoteCodigo.value] || 'moneda cotizada')
  const tipoCodigo = computed(() => (form.tipo === 'venta' ? 'venta_usd' : 'compra_usd'))

  const monedasDelPar = computed(() => {
    const todas = monedas.value || []
    const codigos = [monedaSel.value, quoteCodigo.value].filter(Boolean)
    return todas.filter(m => codigos.includes(m.codigo))
  })

  const monedaForeignId = computed(() => {
    const m = monedas.value.find(m => m.codigo === monedaSel.value)
    return m ? m.id : null
  })
  const monedaQuoteId = computed(() => {
    const m = monedas.value.find(m => m.codigo === quoteCodigo.value)
    return m ? m.id : null
  })

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

  let txCounter = 0
  function nuevaTx() {
    return {
      _key: ++txCounter,
      cuenta_origen_id: null,
      cuenta_destino_id: null,
      moneda_id: null,
      monto: '',
      comision_tipo: 'sin_comision',
      comision_monto: '',
    }
  }

  const form = reactive({
    tipo: 'compra',
    fecha: today,
    monto_usd: '',
    bolivares: '',
    tasa: '',
    descripcion: '',
    transacciones: [nuevaTx(), nuevaTx()],
  })

  const clienteTieneCuentas = computed(() => {
    if (!clienteSeleccionado.value.id) return false
    return cuentas.value.some(c => c.cliente_id === clienteSeleccionado.value.id)
  })

  const titulo = computed(() => {
    if (esEdicion.value) return `Editar operación #${editId.value}`
    return `Nueva ${form.tipo === 'venta' ? 'Venta' : 'Compra'} ${monedaSel.value}`
  })

  const resumenTransacciones = computed(() => {
    const agrupado = {}
    for (const tx of form.transacciones) {
      if (!tx.moneda_id || !tx.monto) continue
      agrupado[tx.moneda_id] = (agrupado[tx.moneda_id] || 0) + parseFloat(tx.monto)
    }
    const result = []
    for (const [monedaId, esperado, label] of [
      [monedaForeignId.value, parseFloat(form.monto_usd) || 0, `Total ${monedaSel.value}`],
      [monedaQuoteId.value, parseFloat(form.bolivares) || 0, `Total ${quoteCodigo.value}`],
    ]) {
      if (!monedaId) continue
      const total = agrupado[monedaId] || 0
      const diff = Math.abs(total - esperado)
      const ok = (total === 0 && esperado === 0) || (total > 0 && diff < 0.01)
      result.push({ label, total: total.toFixed(2), esperado: esperado.toFixed(2), diferencia: diff.toFixed(2), ok })
    }
    return result
  })

  const formularioValido = computed(() => {
    if (!form.tasa || parseFloat(form.tasa) <= 0) return false
    if (!clienteSeleccionado.value.id) return false
    if (form.transacciones.length === 0) return false
    for (const tx of form.transacciones) {
      if (!tx.cuenta_origen_id || !tx.cuenta_destino_id || !tx.moneda_id || !parseFloat(tx.monto)) return false
    }
    if (resumenTransacciones.value.some(r => !r.ok)) return false
    if (tasaDesfavorable.value && !form.descripcion.trim()) return false
    return true
  })

  const resumenItems = computed(() => {
    const items = [
      { label: 'Tipo', value: form.tipo === 'venta' ? `Venta de ${monedaSel.value}` : `Compra de ${monedaSel.value}` },
      { label: 'Cliente', value: clienteSeleccionado.value.nombre || 'Sin cliente' },
      { label: 'Tasa', value: parseFloat(form.tasa).toFixed(2) },
      { label: 'Transacciones', value: `${form.transacciones.length} fila(s)` },
    ]
    const totalComision = form.transacciones.reduce((s, tx) => s + (parseFloat(tx.comision_monto) || 0), 0)
    if (totalComision > 0) {
      items.push({ label: 'Comisión total', value: `${quoteSimbolo.value} ${formatMoney(totalComision)}` })
    }
    return items
  })

  const agregarTransaccion = () => form.transacciones.push(nuevaTx())
  const eliminarTransaccion = (index) => {
    if (form.transacciones.length <= 1) return
    form.transacciones.splice(index, 1)
  }
  const limpiarTransacciones = () => { form.transacciones = [nuevaTx(), nuevaTx()] }

  const distribuirMontos = () => {
    const foreignId = monedaForeignId.value
    const quoteId = monedaQuoteId.value
    const montoUSD = parseFloat(form.monto_usd) || 0
    const montoVES = parseFloat(form.bolivares) || 0
    const txsForeign = form.transacciones.filter(tx => tx.moneda_id && tx.moneda_id == foreignId)
    const txsQuote = form.transacciones.filter(tx => tx.moneda_id && tx.moneda_id == quoteId)

    if (txsForeign.length > 0 && montoUSD > 0) {
      const base = Math.floor((montoUSD / txsForeign.length) * 100) / 100
      let resto = parseFloat((montoUSD - base * txsForeign.length).toFixed(2))
      txsForeign.forEach((tx, i) => { tx.monto = i === txsForeign.length - 1 ? (base + resto).toString() : base.toString() })
    }
    if (txsQuote.length > 0 && montoVES > 0) {
      const base = Math.floor((montoVES / txsQuote.length) * 100) / 100
      let resto = parseFloat((montoVES - base * txsQuote.length).toFixed(2))
      txsQuote.forEach((tx, i) => { tx.monto = i === txsQuote.length - 1 ? (base + resto).toString() : base.toString() })
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

    const movs = op.movimientos || []
    const movsForeign = movs.filter(m => m.moneda?.codigo === monedaSel.value)
    const movsQuote = movs.filter(m => m.moneda?.codigo === quoteCodigo.value)

    form.monto_usd = movsForeign.reduce((s, m) => s + Math.abs(parseFloat(m.monto)), 0).toFixed(2)
    form.bolivares = movsQuote.reduce((s, m) => s + Math.abs(parseFloat(m.monto)), 0).toFixed(2)

    form.transacciones = []
    for (const mov of movs) {
      const signo = parseFloat(mov.monto) >= 0 ? 'destino' : 'origen'
      form.transacciones.push({
        _key: ++txCounter,
        cuenta_origen_id: signo === 'origen' ? mov.cuenta_id : null,
        cuenta_destino_id: signo === 'destino' ? mov.cuenta_id : null,
        moneda_id: mov.moneda_id,
        monto: Math.abs(parseFloat(mov.monto)).toString(),
        comision_tipo: 'sin_comision',
        comision_monto: '',
      })
    }
    if (form.transacciones.length === 0) form.transacciones = [nuevaTx(), nuevaTx()]
  }

  const recargarCuentas = async () => {
    try {
      await cuentasComposable.fetchAll()
      cuentas.value = cuentasComposable.cuentas.value || []
    } catch (e) {
      console.warn('Error al recargar cuentas:', e)
      cuentas.value = []
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
      const movimientos = []
      let totalComision = 0

      for (const tx of form.transacciones) {
        if (!tx.cuenta_origen_id || !tx.cuenta_destino_id || !tx.moneda_id || !parseFloat(tx.monto)) continue
        const monto = roundTo(Math.abs(parseFloat(tx.monto)))
        const comision = roundTo(parseFloat(tx.comision_monto) || 0)
        movimientos.push({ cuenta_id: Number(tx.cuenta_origen_id), monto: -roundTo(monto + comision) })
        movimientos.push({ cuenta_id: Number(tx.cuenta_destino_id), monto })
        totalComision += comision
      }

      if (movimientos.length < 2) {
        error.value = 'Debes configurar al menos una transacción completa.'
        return
      }

      totalComision = roundTo(totalComision)

      const body = {
        fecha: form.fecha,
        tipo_codigo: tipoCodigo.value,
        operador_id: Number(auth.user.value.id),
        tasa_aplicada: roundTo(parseFloat(form.tasa)),
        descripcion: form.descripcion.trim() || null,
        movimientos,
      }

      if (clienteSeleccionado.value.id) {
        body.cliente_id = Number(clienteSeleccionado.value.id)
      }

      if (totalComision > 0) {
        body.genera_comision = true
        body.monto_comision = totalComision
      } else {
        body.genera_comision = false
        body.monto_comision = 0
      }

      if (esEdicion.value) {
        body.motivo_edicion = motivoEdicion.value
        await operaciones.update(editId.value, body)
      } else {
        await operaciones.create(body)
      }

      const op = operaciones.detail.value
      successRef.value = op?.referencia ? `(${op.referencia})` : `#${op?.id || ''}`
      notifier.success('Operación registrada exitosamente')
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
      monto_usd: '',
      bolivares: '',
      tasa: tasaSugerida.value || '',
      descripcion: '',
      transacciones: [nuevaTx(), nuevaTx()],
    })
    tasas.fetchVigentes()
  }

  const init = async () => {
    await tasas.fetchVigentes()
    await recargarCuentas()
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
    cuentas,
    monedas,
    monedasDelPar,
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
    quoteNombre,
    parStr,
    clienteTieneCuentas,
    titulo,
    tasaPar,
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
  }
}
