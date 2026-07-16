import { describe, it, expect } from 'vitest'
import { useFormatting } from '../useFormatting'

describe('useFormatting', () => {
  const { formatMoney, formatVes, formatRate, formatDate, formatHora, formatDateTime, formatTamano, formatUsd } = useFormatting()

  describe('formatMoney', () => {
    it('formatea números en USD con 2 decimales', () => {
      expect(formatMoney(1234.56, 'USD')).toBe('$ 1,234.56')
      expect(formatMoney(1000, 'USD')).toBe('$ 1,000.00')
    })

    it('formatea números en VES con símbolo Bs.', () => {
      expect(formatMoney(50.5, 'VES')).toBe('Bs. 50.50')
    })

    it('maneja valores nulos o inválidos', () => {
      expect(formatMoney(null)).toBe('0.00')
      expect(formatMoney('abc')).toBe('0.00')
      expect(formatMoney()).toBe('0.00')
    })
  })

  describe('formatVes', () => {
    it('formatea como VES con 2 decimales', () => {
      expect(formatVes(100.2)).toBe('Bs. 100.20')
    })
  })

  describe('formatRate', () => {
    it('formatea tasas con 2 decimales', () => {
      expect(formatRate(40.5)).toBe('40.50')
      expect(formatRate(41.123456)).toMatch(/^41\.12/)
    })

    it('maneja valores inválidos', () => {
      expect(formatRate(null)).toBe('0.00')
      expect(formatRate('abc')).toBe('0.00')
    })
  })

  describe('formatDate', () => {
    it('formatea fechas usando toLocaleDateString', () => {
      const date = new Date(2026, 6, 15)
      const result = formatDate(date)
      expect(result).toContain('07/2026')
    })

    it('retorna string vacío para fechas inválidas', () => {
      expect(formatDate(null)).toBe('')
      expect(formatDate('invalid')).toBe('')
    })
  })

  describe('formatHora', () => {
    it('retorna string con hora para fecha válida', () => {
      const result = formatHora('2026-07-15T14:30:00')
      expect(result).toBeTruthy()
      expect(typeof result).toBe('string')
    })
  })

  describe('formatDateTime', () => {
    it('retorna string con fecha y hora', () => {
      const result = formatDateTime('2026-07-15T14:30:00')
      expect(result).toBeTruthy()
      expect(typeof result).toBe('string')
    })
  })

  describe('formatTamano', () => {
    it('formatea bytes a KB, MB, GB', () => {
      expect(formatTamano(500)).toBe('500.00 B')
      expect(formatTamano(1024)).toBe('1.00 KB')
      expect(formatTamano(1048576)).toBe('1.00 MB')
      expect(formatTamano(1073741824)).toBe('1.00 GB')
    })

    it('maneja cero bytes', () => {
      expect(formatTamano(0)).toBe('0 B')
    })
  })

  describe('formatUsd', () => {
    it('formatea como USD sin pasar moneda', () => {
      expect(formatUsd(1500)).toBe('$ 1,500.00')
    })
  })
})
