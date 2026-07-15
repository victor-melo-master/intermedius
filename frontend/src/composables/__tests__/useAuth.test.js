import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useAuth } from '../useAuth'
import api from '@/api/axios'

vi.mock('@/api/axios', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

describe('useAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('inicializa con token vacío si no hay en localStorage', () => {
    const auth = useAuth()
    expect(auth.token.value).toBeNull()
    expect(auth.isAuthenticated.value).toBe(false)
  })

  it('carga token desde localStorage al iniciar', () => {
    localStorage.setItem('token', 'mi-token-falso')
    const auth = useAuth()
    expect(auth.token.value).toBe('mi-token-falso')
  })

  it('login guarda token y usuario', async () => {
    const mockResponse = {
      data: {
        token: 'nuevo-token',
        user: { id: 1, name: 'Test', roles: ['admin'] },
      },
    }
    api.post.mockResolvedValue(mockResponse)

    const auth = useAuth()
    await auth.login({ email: 'test@test.com', password: '123' })

    expect(api.post).toHaveBeenCalledWith('/auth/login', { email: 'test@test.com', password: '123' }, expect.any(Object))
    expect(auth.token.value).toBe('nuevo-token')
    expect(auth.user.value).toEqual({ id: 1, name: 'Test', roles: ['admin'] })
    expect(localStorage.getItem('token')).toBe('nuevo-token')
  })

  it('logout elimina token y usuario', async () => {
    localStorage.setItem('token', 'mi-token')
    const auth = useAuth()
    auth.user.value = { id: 1 }
    auth.token.value = 'mi-token'

    api.post.mockResolvedValue({})

    await auth.logout()

    expect(api.post).toHaveBeenCalledWith('/auth/logout')
    expect(auth.token.value).toBeNull()
    expect(auth.user.value).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
  })

  describe('helpers de roles', () => {
    it('isAdmin es true para admin y super_admin', () => {
      const auth = useAuth()
      auth.user.value = { roles: ['admin'] }
      expect(auth.isAdmin.value).toBe(true)

      auth.user.value = { roles: ['super_admin'] }
      expect(auth.isAdmin.value).toBe(true)

      auth.user.value = { roles: ['pagador'] }
      expect(auth.isAdmin.value).toBe(false)
    })

    it('hasRole verifica un rol específico', () => {
      const auth = useAuth()
      auth.user.value = { roles: ['admin', 'pagador'] }

      expect(auth.hasRole('admin')).toBe(true)
      expect(auth.hasRole('pagador')).toBe(true)
      expect(auth.hasRole('operador')).toBe(false)
    })

    it('hasAnyRole verifica múltiples roles', () => {
      const auth = useAuth()
      auth.user.value = { roles: ['pagador'] }

      expect(auth.hasAnyRole(['admin', 'super_admin'])).toBe(false)
      expect(auth.hasAnyRole(['pagador', 'admin'])).toBe(true)
    })
  })
})
