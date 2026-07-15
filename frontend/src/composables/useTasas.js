import { ref } from "vue";
import { useApi } from "./useApi";
import api from "@/api/axios";

export function useTasas() {
  const { execute, loading, error } = useApi();
  const vigentes = ref([]);
  const historico = ref([]);

  const fetchVigentes = async () => {
    try {
      const response = await execute((signal) =>
        api.get("/configuracion/tasas-vigentes", { signal }),
      );
      vigentes.value = Array.isArray(response?.data)
        ? response.data
        : response?.data?.data || [];
    } catch (err) {
      console.warn("Error al cargar tasas vigentes:", err);
      vigentes.value = []; // Fallback a array vacío para no romper
    }
  };

  const fetchHistorico = async (params = {}) => {
    const response = await execute((signal) =>
      api.get("/configuracion/tasas-diarias/historico", { params, signal }),
    );
    historico.value = response?.data?.data || response?.data || [];
    return response;
  };

  const getTasaPar = (baseCodigo, cotizadaCodigo) => {
    return vigentes.value.find(
      (t) =>
        t.moneda_base?.codigo === baseCodigo &&
        t.moneda_cotizada?.codigo === cotizadaCodigo,
    );
  };

  return {
    vigentes,
    historico,
    fetchVigentes,
    fetchHistorico,
    getTasaPar,
    loading,
    error,
  };
}
