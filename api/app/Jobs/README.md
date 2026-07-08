# Jobs/ — Background Jobs

Procesados por Laravel Horizon (Redis queue). Todos implementan `ShouldQueue`.

| Job | Schedule | Descripción |
|---|---|---|
| `SincronizarTasasJob` | Cada 1 minuto | Obtiene tasas de mercado (BCV, paralelo, Binance P2P) |
| `SincronizarTasasReferenciaJob` | Cada 1 minuto | Sincroniza tasas de referencia adicionales |
| `AlertarTasasFaltantesJob` | Diario 08:00 y 14:00 | Envía email a admins si faltan tasas del día |
| `GenerarReporteMensualComisionesJob` | 1er día del mes 06:00 | Genera reporte de comisiones mensual |
| `ProcesarFifoOperacionJob` | On-demand (dispatched) | Procesa FIFO de operaciones |
| `RecalcularSaldoCuentaJob` | On-demand | Recalcula saldo de una cuenta |
| `AutoArchivarClientesInactivos` | Domingo 03:00 | Soft-delete clientes sin operaciones en 4+ meses |
