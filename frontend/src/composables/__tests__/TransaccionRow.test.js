import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TransaccionRow from '../../components/operaciones/TransaccionRow.vue'

const monedas = [
  { id: 1, codigo: 'USD', nombre: 'Dólar' },
  { id: 2, codigo: 'VES', nombre: 'Bolívar' },
]

const cuentas = [
  { id: 1, alias: 'Cta Cliente USD', banco: { nombre: 'Banco A' }, moneda_id: 1, moneda: { codigo: 'USD' }, titular_id: null, cliente_id: 10, saldo_cache: '5000' },
  { id: 2, alias: 'Cta Intermedius USD', banco: { nombre: 'Banco B' }, moneda_id: 1, moneda: { codigo: 'USD' }, titular_id: 99, cliente_id: null, saldo_cache: '10000' },
  { id: 3, alias: 'Cta Cliente VES', banco: { nombre: 'Banco A' }, moneda_id: 2, moneda: { codigo: 'VES' }, titular_id: null, cliente_id: 10, saldo_cache: '500000' },
  { id: 4, alias: 'Cta Intermedius VES', banco: { nombre: 'Banco B' }, moneda_id: 2, moneda: { codigo: 'VES' }, titular_id: 99, cliente_id: null, saldo_cache: '2000000' },
  { id: 5, alias: 'Cta Otro USD', banco: { nombre: 'Banco C' }, moneda_id: 1, moneda: { codigo: 'USD' }, titular_id: 88, cliente_id: null, saldo_cache: '3000' },
]

describe('TransaccionRow - cuentasOrigen / cuentasDestino', () => {
  describe('VENTA de USD (Foreign)', () => {
    const baseProps = {
      index: 0,
      monedas,
      cuentas,
      monedaId: 1,
      tipoOperacion: 'venta',
      clienteId: 10,
      intermediusTitularId: 99,
      monedaForeignId: 1,
      monedaQuoteId: 2,
      cuentaOrigenId: null,
      cuentaDestinoId: null,
      monto: '',
    }

    it('origen = cuentas Intermedius (vende USD)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 1 } })
      const optionsOrigen = wrapper.findAll('select')[1].findAll('option')
      const labelsOrigen = optionsOrigen.map(o => o.text())
      expect(labelsOrigen.some(l => l.includes('Cta Intermedius USD'))).toBe(true)
      expect(labelsOrigen.some(l => l.includes('Cta Cliente USD'))).toBe(false)
    })

    it('destino = cuentas del cliente (compra USD)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 1 } })
      const optionsDestino = wrapper.findAll('select')[2].findAll('option')
      const labelsDestino = optionsDestino.map(o => o.text())
      expect(labelsDestino.some(l => l.includes('Cta Cliente USD'))).toBe(true)
      expect(labelsDestino.some(l => l.includes('Cta Intermedius USD'))).toBe(false)
    })
  })

  describe('VENTA de USD — moneda VES (Quote)', () => {
    const baseProps = {
      index: 0,
      monedas,
      cuentas,
      tipoOperacion: 'venta',
      clienteId: 10,
      intermediusTitularId: 99,
      monedaForeignId: 1,
      monedaQuoteId: 2,
      cuentaOrigenId: null,
      cuentaDestinoId: null,
      monto: '',
    }

    it('origen = cuentas del cliente (paga VES)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 2 } })
      const optionsOrigen = wrapper.findAll('select')[1].findAll('option')
      const labelsOrigen = optionsOrigen.map(o => o.text())
      expect(labelsOrigen.some(l => l.includes('Cta Cliente VES'))).toBe(true)
      expect(labelsOrigen.some(l => l.includes('Cta Intermedius VES'))).toBe(false)
    })

    it('destino = cuentas Intermedius (recibe VES)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 2 } })
      const optionsDestino = wrapper.findAll('select')[2].findAll('option')
      const labelsDestino = optionsDestino.map(o => o.text())
      expect(labelsDestino.some(l => l.includes('Cta Intermedius VES'))).toBe(true)
      expect(labelsDestino.some(l => l.includes('Cta Cliente VES'))).toBe(false)
    })
  })

  describe('COMPRA de USD (Foreign)', () => {
    const baseProps = {
      index: 0,
      monedas,
      cuentas,
      monedaId: 1,
      tipoOperacion: 'compra',
      clienteId: 10,
      intermediusTitularId: 99,
      monedaForeignId: 1,
      monedaQuoteId: 2,
      cuentaOrigenId: null,
      cuentaDestinoId: null,
      monto: '',
    }

    it('origen = cuentas del cliente (vende USD)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 1 } })
      const optionsOrigen = wrapper.findAll('select')[1].findAll('option')
      const labelsOrigen = optionsOrigen.map(o => o.text())
      expect(labelsOrigen.some(l => l.includes('Cta Cliente USD'))).toBe(true)
      expect(labelsOrigen.some(l => l.includes('Cta Intermedius USD'))).toBe(false)
    })

    it('destino = cuentas Intermedius (recibe USD)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 1 } })
      const optionsDestino = wrapper.findAll('select')[2].findAll('option')
      const labelsDestino = optionsDestino.map(o => o.text())
      expect(labelsDestino.some(l => l.includes('Cta Intermedius USD'))).toBe(true)
      expect(labelsDestino.some(l => l.includes('Cta Cliente USD'))).toBe(false)
    })
  })

  describe('COMPRA de USD — moneda VES (Quote)', () => {
    const baseProps = {
      index: 0,
      monedas,
      cuentas,
      tipoOperacion: 'compra',
      clienteId: 10,
      intermediusTitularId: 99,
      monedaForeignId: 1,
      monedaQuoteId: 2,
      cuentaOrigenId: null,
      cuentaDestinoId: null,
      monto: '',
    }

    it('origen = cuentas Intermedius (paga VES)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 2 } })
      const optionsOrigen = wrapper.findAll('select')[1].findAll('option')
      const labelsOrigen = optionsOrigen.map(o => o.text())
      expect(labelsOrigen.some(l => l.includes('Cta Intermedius VES'))).toBe(true)
      expect(labelsOrigen.some(l => l.includes('Cta Cliente VES'))).toBe(false)
    })

    it('destino = cuentas del cliente (recibe VES)', () => {
      const wrapper = mount(TransaccionRow, { props: { ...baseProps, monedaId: 2 } })
      const optionsDestino = wrapper.findAll('select')[2].findAll('option')
      const labelsDestino = optionsDestino.map(o => o.text())
      expect(labelsDestino.some(l => l.includes('Cta Cliente VES'))).toBe(true)
      expect(labelsDestino.some(l => l.includes('Cta Intermedius VES'))).toBe(false)
    })
  })

  describe('sin clienteId o intermediusTitularId', () => {
    it('muestra todas las cuentas si falta clienteId', () => {
      const wrapper = mount(TransaccionRow, {
        props: {
          index: 0,
          monedas,
          cuentas,
          monedaId: 1,
          tipoOperacion: 'venta',
          clienteId: null,
          intermediusTitularId: 99,
          monedaForeignId: 1,
          monedaQuoteId: 2,
          cuentaOrigenId: null,
          cuentaDestinoId: null,
          monto: '',
        },
      })
      const optionsOrigen = wrapper.findAll('select')[1].findAll('option')
      expect(optionsOrigen.length).toBe(4) // placeholder + 3 USD cuentas
    })
  })
})
