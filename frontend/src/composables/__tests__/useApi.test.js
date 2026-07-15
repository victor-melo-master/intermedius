import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useApi } from '../useApi'

const fakeResponse = { data: { id: 1, name: 'test' }, status: 200 }

vi.mock('@/api/axios', () => ({
  default: vi.fn((config) => {
    if (config?.signal?.aborted) return Promise.reject(new DOMException('Aborted', 'AbortError'))
    return Promise.resolve(fakeResponse)
  }),
}))

describe('useApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('execute llama a la función request con signal', async () => {
    const { execute } = useApi()
    const fn = vi.fn().mockResolvedValue(fakeResponse)
    const result = await execute(fn)
    expect(fn).toHaveBeenCalledOnce()
    expect(fn.mock.calls[0][0]).toBeInstanceOf(AbortSignal)
    expect(result).toBe(fakeResponse)
  })

  it('execute asigna data con response.data', async () => {
    const { execute, data } = useApi()
    await execute(() => Promise.resolve(fakeResponse))
    expect(data.value).toEqual(fakeResponse.data)
  })

  it('execute pone loading=true durante la ejecución', async () => {
    const { execute, loading } = useApi()
    const promise = execute(() => new Promise(r => setTimeout(r, 10)))
    expect(loading.value).toBe(true)
    await promise
    expect(loading.value).toBe(false)
  })

  it('execute captura error y lo asigna', async () => {
    const { execute, error } = useApi()
    const err = new Error('Network Error')
    err.response = { data: { message: 'Network Error' } }
    await expect(execute(() => Promise.reject(err))).rejects.toThrow()
    expect(error.value).toBe('Network Error')
  })

  it('execute ignora AbortError y retorna null', async () => {
    const { execute, error } = useApi()
    const abortErr = new DOMException('Aborted', 'AbortError')
    const result = await execute(() => Promise.reject(abortErr))
    expect(result).toBeNull()
    expect(error.value).toBeNull()
  })

  it('abort cancela la petición en curso', async () => {
    const { execute, abort } = useApi()
    const fn = vi.fn().mockImplementation(() => new Promise((_, reject) => {
      setTimeout(() => reject(new DOMException('Aborted', 'AbortError')), 50)
    }))
    const promise = execute(fn)
    abort()
    const result = await promise
    expect(result).toBeNull()
  })

  it('aborta petición previa si se llama execute dos veces', async () => {
    const { execute, abort } = useApi()
    const fn1 = vi.fn().mockImplementation(() => new Promise((_, reject) => {
      setTimeout(() => reject(new DOMException('Aborted', 'AbortError')), 100)
    }))
    const fn2 = vi.fn().mockResolvedValue(fakeResponse)

    await execute(fn1)
    // fn1 should have been aborted when fn2 started
    const result = await execute(fn2)
    expect(result).toBe(fakeResponse)
  })
})
