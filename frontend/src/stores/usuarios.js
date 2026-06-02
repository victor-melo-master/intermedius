import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/axios.js'

export const useUsuariosStore = defineStore('usuarios', () => {
  const list = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchAll() {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/usuarios')
      list.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.response?.data?.message || err.message
    } finally {
      loading.value = false
    }
  }

  async function create(body) {
    const { data } = await api.post('/usuarios', body)
    list.value = []
    return data
  }

  async function update(id, body) {
    const { data } = await api.put(`/usuarios/${id}`, body)
    list.value = []
    return data
  }

  async function toggleActivo(usuario) {
    const { data } = await api.put(`/usuarios/${usuario.id}`, { activo: !usuario.activo })
    list.value = []
    return data
  }

  return { list, loading, error, fetchAll, create, update, toggleActivo }
})
