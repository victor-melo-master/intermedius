# router/ — AI Context

## Configuración

- **Modo**: `createWebHistory()`
- **Guard**: `router.beforeEach` verifica token en localStorage
  - Sin token + ruta no pública → redirect `/login`
  - Con token + ruta `/login` → redirect `/operaciones`
  - Ruta no encontrada (`/:pathMatch(.*)*`) → redirect `/operaciones`

## Rutas detalladas

### Públicas
| Path | Name | Componente |
|---|---|---|
| `/login` | login | `LoginView` |
| `/email/verify` | EmailVerify | `EmailVerifyView` |

### Protegidas (require token)
| Path | Name | Componente |
|---|---|---|
| `/operaciones` | operaciones | `OperacionesView` |
| `/operaciones/nueva` | nueva-operacion | `OperacionFormView` |
| `/operaciones/nueva/intermediada` | intermediada | `OperacionIntermediadaForm` |
| `/operaciones/:id` | detalle-operacion | `OperacionDetailView` |
| `/operaciones/moneda/:moneda` | operaciones-moneda | `OperacionMonedaView` |
| `/cuentas` | cuentas | `CuentasView` |
| `/clientes` | clientes | `ClientesView` |
| `/titulares` | titulares | `TitularesView` |
| `/bancos` | bancos | `BancosView` |
| `/tasas` | tasas | `TasasView` |
| `/dashboard` | dashboard | `DashboardView` |
| `/pool` | pool | `PoolView` |
| `/reportes` | reportes | `ReportesView` |
| `/comisiones` | comisiones | `ComisionesView` |
| `/usuarios` | usuarios | `UsuariosView` |
| `/:pathMatch(.*)*` | — | redirect a `/operaciones` |

### Sidebar navigation

Definido en `AppShell.vue` con `baseNav` array:
- Operaciones, Cuentas, Clientes, Titulares, Bancos, Tasas, Dashboard, Pool, Reportes, Comisiones, Usuarios

Íconos: emojis (📊, 🏦, 👥, etc.)
