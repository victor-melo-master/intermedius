/**
 * Utilidades para el historial de sesiones de usuario.
 */

/**
 * Extrae navegador y SO a partir del user agent (regex simple, sin librerías).
 * @param {string|null} userAgent
 * @returns {{ navegador: string, so: string }} Etiquetas legibles o '—'
 */
export function parseUserAgent(userAgent) {
  const ua = userAgent || ''
  const so = [
    [/Windows NT (\d+(\.\d+)?)/, 'Windows'],
    [/Android/, 'Android'],
    [/iPhone/, 'iPhone'],
    [/iPad/, 'iPad'],
    [/Mac OS X/, 'macOS'],
    [/Linux/, 'Linux'],
  ].find(([re]) => re.test(ua))?.[1] || '—'

  const navegador = [
    [/Edg(e)?\//, 'Edge'],
    [/OPR\//, 'Opera'],
    [/Chrome\//, 'Chrome'],
    [/Firefox\//, 'Firefox'],
    [/Safari\//, 'Safari'],
  ].find(([re]) => re.test(ua))?.[1] || '—'

  return { navegador, so }
}
