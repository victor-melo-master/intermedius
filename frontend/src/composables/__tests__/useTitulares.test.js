import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useTitulares } from '../useTitulares'
import api from '@/api/axios'

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn(),
  },
}))

const titularesMock = [
  { id: 1, nombre: 'Pedro Pérez', alias: 'Pedro' },
  { id: 2, nombre: 'Intermedius', alias: 'Casa Matriz' },
  { id: 3, nombre: 'María López', alias: 'María' },
]

describe('useTitulares', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetchAll carga lista plana cuando la API devuelve array', async () => {
    api.get.mockResolvedValue({ data: titularesMock })
    const { fetchAll, list } = useTitulares()
    await fetchAll()
    expect(list.value).toEqual(titularesMock)
    expect(list.value).toHaveLength(3)
  })

  it('fetchAll extrae .data cuando API devuelve paginado', async () => {
    api.get.mockResolvedValue({ data: { data: titularesMock } })
    const { fetchAll, list } = useTitulares()
    await fetchAll()
    expect(list.value).toEqual(titularesMock)
  })

  it('fetchAll asigna array vacío en caso de error', async () => {
    api.get.mockRejectedValue(new Error('Error'))
    const { fetchAll, list } = useTitulares()
    await fetchAll()
    expect(list.value).toEqual([])
  })

  it('getIntermedius retorna el titular con nombre Intermedius', () => {
    const { list, getIntermedius } = useTitulares()
    list.value = titularesMock
    const intermedius = getIntermedius()
    expect(intermedius).toEqual({ id: 2, nombre: 'Intermedius', alias: 'Casa Matriz' })
  })

  it('getIntermedius retorna null si no existe Intermedius', () => {
    const { list, getIntermedius } = useTitulares()
    list.value = [{ id: 1, nombre: 'Otro' }]
    expect(getIntermedius()).toBeNull()
  })

  it('getIntermedius retorna null si la lista está vacía', () => {
    const { list, getIntermedius } = useTitulares()
    list.value = []
    expect(getIntermedius()).toBeNull()
  })
})
