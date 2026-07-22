import { useApi } from './useApi'
import api from '@/api/axios'

export function useMovimientos() {
  const { execute, loading, error } = useApi()

  const agregar = async (operacionId, payload) => {
    const response = await execute((signal) =>
      api.post(`/operaciones/${operacionId}/transacciones`, payload, { signal })
    )
    return response
  }

  const editar = async (operacionId, txId, payload) => {
    const response = await execute((signal) =>
      api.put(`/operaciones/${operacionId}/transacciones/${txId}`, payload, { signal })
    )
    return response
  }

  const confirmar = async (operacionId, txId) => {
    const response = await execute((signal) =>
      api.patch(`/operaciones/${operacionId}/transacciones/${txId}/confirmar`, null, { signal })
    )
    return response
  }

  const revertir = async (operacionId, txId, motivo) => {
    const response = await execute((signal) =>
      api.patch(`/operaciones/${operacionId}/transacciones/${txId}/revertir`, { motivo }, { signal })
    )
    return response
  }

  const eliminar = async (operacionId, txId) => {
    const response = await execute((signal) =>
      api.delete(`/operaciones/${operacionId}/transacciones/${txId}`, { signal })
    )
    return response
  }

  return {
    agregar,
    editar,
    confirmar,
    revertir,
    eliminar,
    loading,
    error,
  }
}
