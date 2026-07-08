# Providers — AI Context

## `AppServiceProvider`
- **register()**: No registra nada extra
- **boot()**: 
  - Configuración de gates/policies por defecto
  - Posible registro de observadores si aplica

## `HorizonServiceProvider`
- **boot()**: Configura Laravel Horizon
  - Define `environments`: production, local
  - Define `supervisor-1`: conexión redis, queue `default`, procesos `minProcesses(1)->maxProcesses(5)`, balance `auto`
  - Define `mail` config para notificaciones de Horizon
  - `gate()`: autoriza acceso a Horizon Dashboard — solo `super_admin`
  - Tiempo de espera: 300s por defecto para jobs
