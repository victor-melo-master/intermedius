import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: null, moneda: 'USD' },
    query: {},
  }),
  useRouter: () => ({ push: vi.fn() }),
}))

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: [{ id: 1, codigo: 'USD', nombre: 'Dólar' }, { id: 2, codigo: 'VES', nombre: 'Bolívar' }] }),
  },
}))

vi.mock('../useTasas', () => ({
  useTasas: () => ({
    vigentes: { value: [] },
    fetchVigentes: vi.fn(),
    fetchMonedas: vi.fn(),
  }),
}))

vi.mock('../useAuth', () => ({
  useAuth: () => ({
    user: { value: { id: 1, name: 'Test', roles: ['admin'] } },
  }),
}))

vi.mock('../useOperaciones', () => ({
  useOperaciones: () => ({
    detail: null,
    fetchOne: vi.fn(),
    create: vi.fn().mockResolvedValue({}),
    update: vi.fn().mockResolvedValue({}),
  }),
}))

vi.mock('../useCuentas', () => ({
  useCuentas: () => ({
    cuentas: { value: [] },
    fetchAll: vi.fn(),
    loading: { value: false },
  }),
}))

vi.mock('../useTitulares', () => ({
  useTitulares: () => ({
    getIntermedius: () => ({ id: 99, nombre: 'Intermedius' }),
    fetchAll: vi.fn(),
  }),
}))

vi.mock('../useNotification', () => ({
  useNotification: () => ({
    success: vi.fn(),
    error: vi.fn(),
  }),
}))

vi.mock('../useApiError', () => ({
  useApiError: () => ({
    parseError: (err) => err?.message || 'Error',
  }),
}))

vi.mock('../useFormatting', () => ({
  useFormatting: () => ({
    formatMoney: (v) => new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(v || 0),
  }),
}))

import { useOperacionForm } from '../useOperacionForm'

describe('useOperacionForm', () => {
  let form

  beforeEach(() => {
    vi.clearAllMocks()
    form = useOperacionForm()
  })

  describe('estado inicial', () => {
    it('form tiene valores por defecto', () => {
      expect(form.form.tipo).toBe('compra')
      expect(form.form.monto_usd).toBe('')
      expect(form.form.bolivares).toBe('')
      expect(form.form.tasa).toBe('')
      expect(form.form.descripcion).toBe('')
      expect(form.form.transacciones).toHaveLength(2)
    })

    it('monedaSel por defecto es USD', () => {
      expect(form.monedaSel.value).toBe('USD')
    })

    it('quoteCodigo es VES cuando monedaSel es USD', () => {
      expect(form.quoteCodigo.value).toBe('VES')
    })
  })

  describe('nuevaTx', () => {
    it('crea transacción con valores por defecto', () => {
      const tx = form.form.transacciones[0]
      expect(tx).toHaveProperty('_key')
      expect(tx.cuenta_origen_id).toBeNull()
      expect(tx.cuenta_destino_id).toBeNull()
      expect(tx.moneda_id).toBeNull()
      expect(tx.monto).toBe('')
      expect(tx.comision_tipo).toBe('sin_comision')
      expect(tx.comision_monto).toBe('')
    })
  })

  describe('agregarTransaccion', () => {
    it('agrega una transacción al array', () => {
      const antes = form.form.transacciones.length
      form.agregarTransaccion()
      expect(form.form.transacciones.length).toBe(antes + 1)
    })
  })

  describe('eliminarTransaccion', () => {
    it('elimina una transacción si hay más de 1', () => {
      form.agregarTransaccion()
      const antes = form.form.transacciones.length
      form.eliminarTransaccion(0)
      expect(form.form.transacciones.length).toBe(antes - 1)
    })

    it('no elimina si solo hay 1 transacción', () => {
      form.form.transacciones = [{ _key: 1, monto: '100' }]
      form.eliminarTransaccion(0)
      expect(form.form.transacciones.length).toBe(1)
    })
  })

  describe('limpiarTransacciones', () => {
    it('resetea a 2 transacciones vacías', () => {
      form.agregarTransaccion()
      form.agregarTransaccion()
      form.agregarTransaccion()
      form.limpiarTransacciones()
      expect(form.form.transacciones).toHaveLength(2)
      expect(form.form.transacciones[0].monto).toBe('')
    })
  })

  describe('resumenTransacciones', () => {
    it('retorna array vacío sin monedas cargadas', () => {
      expect(form.resumenTransacciones.value).toEqual([])
    })

    it('retorna resumen correcto con monedas y montos', () => {
      form.monedas.value = [
        { id: 1, codigo: 'USD', nombre: 'Dólar' },
        { id: 2, codigo: 'VES', nombre: 'Bolívar' },
      ]
      form.form.monto_usd = '200'
      form.form.bolivares = '10000'
      form.form.transacciones = [
        { _key: 1, moneda_id: 1, monto: '100' },
        { _key: 2, moneda_id: 1, monto: '100' },
        { _key: 3, moneda_id: 2, monto: '5000' },
        { _key: 4, moneda_id: 2, monto: '5000' },
      ]
      const resumen = form.resumenTransacciones.value
      expect(resumen).toHaveLength(2)
      expect(resumen[0].label).toContain('Total USD')
      expect(resumen[0].total).toBe('200.00')
      expect(resumen[0].ok).toBe(true)
      expect(resumen[1].label).toContain('Total VES')
      expect(resumen[1].total).toBe('10000.00')
      expect(resumen[1].ok).toBe(true)
    })

    it('marca ok=false si hay diferencia', () => {
      form.monedas.value = [
        { id: 1, codigo: 'USD', nombre: 'Dólar' },
        { id: 2, codigo: 'VES', nombre: 'Bolívar' },
      ]
      form.form.monto_usd = '200'
      form.form.transacciones = [
        { _key: 1, moneda_id: 1, monto: '150' },
      ]
      const resumen = form.resumenTransacciones.value
      expect(resumen[0].ok).toBe(false)
      expect(resumen[0].diferencia).not.toBe('0.00')
    })
  })

  describe('distribuirMontos', () => {
    it('distribuye monto USD entre transacciones foreign', () => {
      form.monedas.value = [
        { id: 1, codigo: 'USD', nombre: 'Dólar' },
        { id: 2, codigo: 'VES', nombre: 'Bolívar' },
      ]
      form.form.monto_usd = '100'
      form.form.transacciones = [
        { _key: 1, moneda_id: 1, monto: '' },
        { _key: 2, moneda_id: 1, monto: '' },
        { _key: 3, moneda_id: 2, monto: '' },
      ]
      form.distribuirMontos()
      const txsUSD = form.form.transacciones.filter(t => t.moneda_id === 1)
      const total = txsUSD.reduce((s, t) => s + parseFloat(t.monto), 0)
      expect(total).toBe(100)
    })

    it('distribuye monto VES entre transacciones quote', () => {
      form.monedas.value = [
        { id: 1, codigo: 'USD', nombre: 'Dólar' },
        { id: 2, codigo: 'VES', nombre: 'Bolívar' },
      ]
      form.form.bolivares = '5000'
      form.form.transacciones = [
        { _key: 1, moneda_id: 1, monto: '' },
        { _key: 2, moneda_id: 2, monto: '' },
        { _key: 3, moneda_id: 2, monto: '' },
      ]
      form.distribuirMontos()
      const txsVES = form.form.transacciones.filter(t => t.moneda_id === 2)
      const total = txsVES.reduce((s, t) => s + parseFloat(t.monto), 0)
      expect(total).toBe(5000)
    })
  })

  describe('formularioValido', () => {
    it('es false si no hay tasa', () => {
      expect(form.formularioValido.value).toBe(false)
    })

    it('es false si no hay cliente', () => {
      form.form.tasa = '40'
      expect(form.formularioValido.value).toBe(false)
    })

    it('es false si las transacciones están incompletas', () => {
      form.form.tasa = '40'
      form.clienteSeleccionado.value = { id: 1, nombre: 'Cliente Test' }
      expect(form.formularioValido.value).toBe(false)
    })

    it('es true con datos válidos', () => {
      form.monedas.value = [
        { id: 1, codigo: 'USD', nombre: 'Dólar' },
        { id: 2, codigo: 'VES', nombre: 'Bolívar' },
      ]
      form.form.tasa = '40'
      form.form.monto_usd = '100'
      form.form.bolivares = '4000'
      form.clienteSeleccionado.value = { id: 1, nombre: 'Cliente Test' }
      form.form.transacciones = [
        { _key: 1, moneda_id: 1, monto: '100', cuenta_origen_id: 1, cuenta_destino_id: 2 },
        { _key: 2, moneda_id: 2, monto: '4000', cuenta_origen_id: 2, cuenta_destino_id: 1 },
      ]
      expect(form.formularioValido.value).toBe(true)
    })
  })

  describe('resumenItems', () => {
    it('incluye tipo, cliente, tasa y transacciones', () => {
      form.clienteSeleccionado.value = { id: 1, nombre: 'Cliente Test' }
      form.form.tasa = '40'
      form.form.tipo = 'venta'
      form.monedaSel // already USD
      const items = form.resumenItems.value
      expect(items).toHaveLength(4)
      expect(items[0].value).toContain('Venta')
      expect(items[1].value).toBe('Cliente Test')
      expect(items[2].value).toBe('40.00')
      expect(items[3].value).toContain('2')
    })

    it('incluye comisión total si hay comisiones', () => {
      form.form.transacciones = [
        { _key: 1, comision_monto: '5' },
        { _key: 2, comision_monto: '3' },
      ]
      const items = form.resumenItems.value
      const comision = items.find(i => i.label === 'Comisión total')
      expect(comision).toBeDefined()
    })
  })

})
