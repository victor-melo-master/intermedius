/**
 * Infiere el método de pago según las cuentas origen y destino.
 *
 * Reglas:
 * - USDT → 'binance'
 * - USD + banco Zelle → 'zelle'
 * - USD + banco nacional + banco internacional → 'transferencia_internacional'
 * - USD + ambos nacionales → 'transferencia_usd'
 * - VES + mismo banco nacional → 'pagomovil'
 * - VES + distinto banco nacional → 'transferencia'
 * - Efectivo (sin cuentas o tipo efectivo) → 'efectivo'
 * - Ambos VES nacionales ambiguo → null (decisión usuario entre Pago Móvil o Transferencia)
 */
export function useMetodoPago() {
  function detectar(cuentaOrigen, cuentaDestino) {
    if (!cuentaOrigen || !cuentaDestino) return null

    const tipoOrigen = cuentaOrigen.tipo || ''
    const tipoDestino = cuentaDestino.tipo || ''
    const monedaOrigen = cuentaOrigen.moneda?.codigo || ''
    const monedaDestino = cuentaDestino.moneda?.codigo || ''
    const bancoOrigen = cuentaOrigen.banco
    const bancoDestino = cuentaDestino.banco

    if (tipoOrigen === 'efectivo' || tipoDestino === 'efectivo') {
      return 'efectivo'
    }

    if (monedaOrigen === 'USDT' || monedaDestino === 'USDT') {
      return 'binance'
    }

    if (monedaOrigen === 'USD' && monedaDestino === 'USD') {
      if (tipoOrigen === 'zelle' && tipoDestino === 'zelle') return 'zelle'

      const origenNacional = bancoOrigen?.pais === 'VE'
      const destinoNacional = bancoDestino?.pais === 'VE'

      if (origenNacional && !destinoNacional) return 'transferencia_internacional'
      if (!origenNacional && destinoNacional) return 'transferencia_internacional'
      return 'transferencia_usd'
    }

    if (monedaOrigen === 'VES' && monedaDestino === 'VES') {
      const mismoBanco = bancoOrigen?.id === bancoDestino?.id
      if (mismoBanco) return 'pagomovil'
      return 'transferencia'
    }

    return null
  }

  const opcionesValidas = [
    { value: 'transferencia', label: 'Transferencia' },
    { value: 'pagomovil', label: 'Pago Móvil' },
    { value: 'zelle', label: 'Zelle' },
    { value: 'binance', label: 'Binance' },
    { value: 'efectivo', label: 'Efectivo' },
    { value: 'transferencia_internacional', label: 'Transferencia Internacional' },
    { value: 'transferencia_usd', label: 'Transferencia en USD' },
  ]

  return { detectar, opcionesValidas }
}
