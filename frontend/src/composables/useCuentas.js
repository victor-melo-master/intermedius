import { ref, computed } from "vue";
import { useApi } from "./useApi";
import api from "@/api/axios";

export function useCuentas() {
  const { execute, loading, error } = useApi();
  const cuentas = ref([]);

  const fetchAll = async (params = {}) => {
    try {
      const response = await execute((signal) =>
        api.get("/cuentas", { params, signal }),
      );
      cuentas.value = Array.isArray(response?.data)
        ? response.data
        : response?.data?.data || [];
    } catch (err) {
      console.warn("Error al cargar cuentas:", err);
      cuentas.value = [];
    }
  };

  const filtrarPorMoneda = (monedaId) => {
    return cuentas.value.filter((c) => c.moneda_id == monedaId);
  };

  const filtrarPorCliente = (clienteId) => {
    return cuentas.value.filter((c) => c.cliente_id == clienteId);
  };

  const filtrarPorTitular = (titularId) => {
    return cuentas.value.filter((c) => c.titular_id == titularId);
  };

  const getSaldo = (cuentaId) => {
    const cuenta = cuentas.value.find((c) => c.id == cuentaId);
    return cuenta?.saldo_cache || 0;
  };

  return {
    cuentas,
    fetchAll,
    filtrarPorMoneda,
    filtrarPorCliente,
    filtrarPorTitular,
    getSaldo,
    loading,
    error,
  };
}
