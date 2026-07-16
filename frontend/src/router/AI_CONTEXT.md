# router/ — AI Context

> Vue Router 4 con `createWebHistory()`, 19 rutas y guard `beforeEach` para autenticación.

## index.js

### Configuración

- **Modo**: `createWebHistory()`
- **Guard**: `router.beforeEach` verifica token en `localStorage`
  - Sin token + ruta no pública → redirect `/login`
  - Con token + ruta `/login` → redirect `/operaciones`
  - Ruta no encontrada (`/:pathMatch(.*)*`) → redirect `/operaciones`

### Rutas

#### Públicas (sin auth)
| Path | Name | Componente |
|---|---|---|
| `/login` | login | `LoginView` |
| `/email/verify` | EmailVerify | `EmailVerifyView` |

#### Protegidas (requiere token)
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

#### Catch-all
| Path | Componente |
|---|---|
| `/:pathMatch(.*)*` | redirect → `/operaciones` |

### Sidebar navigation (AppShell.vue)

Definido en `baseNav` array con items:
- Operaciones, Cuentas, Clientes, Titulares, Bancos, Tasas, Dashboard, Pool, Reportes, Comisiones, Usuarios
- Cada item tiene: `label`, `path`, `icon` (emoji), `roles` (array de roles permitidos)
- Filtrado por roles: `auth.checkRole(item.roles)` oculta items según el rol del usuario

### Roles (desde backend)

| Rol | Acceso |
|---|---|
| `super_admin` | Total |
| `admin` | CRUD completo, verificar, pool, config |
| `operador` | Crear operaciones, ver catálogos |
| `contador` | Reportes, gastos |
| `lectura` | Solo ver |
| `pagador` | Pool (tomar, soltar, pagar) |
