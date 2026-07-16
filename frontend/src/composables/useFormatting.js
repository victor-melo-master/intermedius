export function useFormatting() {
  const roundTo = (value, decimals = 2) => {
    const num = parseFloat(value)
    if (isNaN(num)) return 0
    return Math.round((num + Number.EPSILON) * Math.pow(10, decimals)) / Math.pow(10, decimals)
  }

  const formatMoney = (value, currency = 'USD', decimals = 2) => {
    const num = parseFloat(value)
    if (isNaN(num)) return '0.00'
    const formatter = new Intl.NumberFormat('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    })
    const symbol = currency === 'USD' ? '$' :
                   currency === 'VES' ? 'Bs.' :
                   currency === 'EUR' ? '€' :
                   currency === 'COP' ? '$' : ''
    return `${symbol} ${formatter.format(num)}`.trim()
  }

  const formatVes = (value) => formatMoney(value, 'VES', 2)

  const formatRate = (value) => {
    const num = parseFloat(value)
    if (isNaN(num)) return '0.0000'
    return num.toFixed(4)
  }

  const formatDate = (date) => {
    if (!date) return ''
    const d = new Date(date)
    if (isNaN(d.getTime())) return ''
    return d.toLocaleDateString('es-VE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  }

  const formatHora = (date) => {
    if (!date) return ''
    const d = new Date(date)
    if (isNaN(d.getTime())) return ''
    return d.toLocaleString('es-VE', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const formatDateTime = (date) => {
    if (!date) return ''
    const d = new Date(date)
    if (isNaN(d.getTime())) return ''
    return d.toLocaleString('es-VE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const formatUsd = (value) => formatMoney(value, 'USD', 2)

  const formatTamano = (bytes) => {
    if (bytes === 0 || !bytes) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    const size = (bytes / Math.pow(k, i)).toFixed(2)
    return `${size} ${sizes[i]}`
  }

  return {
    roundTo,
    formatMoney,
    formatVes,
    formatRate,
    formatDate,
    formatHora,
    formatDateTime,
    formatUsd,
    formatTamano,
  }
}
