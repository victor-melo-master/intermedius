import { describe, it, expect } from 'vitest'
import { useApiError } from '../useApiError'

describe('useApiError', () => {
  const { parseError, getErrorMessage } = useApiError()

  describe('parseError', () => {
    it('extrae errores de validación de Laravel (formato errors.field)', () => {
      const err = {
        response: {
          data: {
            errors: {
              email: ['El email es requerido', 'El email debe ser válido'],
              password: ['La contraseña es muy corta'],
            },
          },
        },
      }
      const result = parseError(err)
      expect(result).toContain('El email es requerido')
      expect(result).toContain('El email debe ser válido')
      expect(result).toContain('La contraseña es muy corta')
    })

    it('extrae mensaje simple de error', () => {
      const err = {
        response: {
          data: {
            message: 'Operación no permitida',
          },
        },
      }
      expect(parseError(err)).toBe('Operación no permitida')
    })

    it('usa err.message si no hay response', () => {
      const err = new Error('Network Error')
      expect(parseError(err)).toBe('Network Error')
    })

    it('retorna "Error desconocido" si todo falla', () => {
      const err = {}
      expect(parseError(err)).toBe('Error desconocido')
    })
  })

  describe('getErrorMessage', () => {
    it('captura errores y devuelve mensaje sin lanzar', () => {
      const err = { response: { data: { message: 'Error controlado' } } }
      expect(getErrorMessage(err)).toBe('Error controlado')
    })

    it('maneja excepciones internas sin romper', () => {
      const err = null
      expect(getErrorMessage(err)).toBe('Error inesperado')
    })
  })
})
