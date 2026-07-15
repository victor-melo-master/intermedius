export function useApiError() {
  const parseError = (err) => {
    const data = err.response?.data
    if (!data) return err.message || 'Error desconocido'

    if (data.errors) {
      return Object.values(data.errors)
        .flat()
        .join('\n')
    }

    return data.message || err.message || 'Error desconocido'
  }

  const getErrorMessage = (err) => {
    try {
      return parseError(err)
    } catch {
      return 'Error inesperado'
    }
  }

  return {
    parseError,
    getErrorMessage,
  }
}
