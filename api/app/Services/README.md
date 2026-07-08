# Services/ — Business Logic Services

```
Services/
├── Comisiones/          # (placeholder)
├── Configuracion/       # TasaDiariaService, CalculadorComisionesService
├── Fifo/                # (placeholder)
├── Operaciones/         # RegistroOperacionService
├── Reportes/            # ReporteComisionesOperadoresService
└── Tasas/               # TasasMercadoService
```

| Service | Archivo | Responsabilidad |
|---|---|---|
| `TasasMercadoService` | `Tasas/TasasMercadoService.php` | Obtener tasas de fuentes externas (BCV, paralelo, Binance P2P) |
| `TasaDiariaService` | `Configuracion/TasaDiariaService.php` | Publicar, obtener vigente, validar tasas diarias |
| `CalculadorComisionesService` | `Configuracion/CalculadorComisionesService.php` | Calcular comisiones según reglas de negocio |
| `RegistroOperacionService` | `Operaciones/RegistroOperacionService.php` | Orquestar registro completo de operación |
| `ReporteComisionesOperadoresService` | `Reportes/ReporteComisionesOperadoresService.php` | Generar reportes Excel/PDF de comisiones |
