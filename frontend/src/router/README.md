# router/ — Vue Router

## Rutas

| Path | Name | Componente | Meta | Auth |
|---|---|---|---|---|
| `/` | — | redirect a `/operaciones` | — | — |
| `/login` | login | `LoginView` | public | No |
| `/operaciones` | operaciones | `OperacionesView` | — | Sí |
| `/operaciones/nueva` | nueva-operacion | `OperacionFormView` | — | Sí |
| `/operaciones/nueva/intermediada` | intermediada | `OperacionIntermediadaForm` | — | Sí |
| `/operaciones/:id` | detalle-operacion | `OperacionDetailView` | — | Sí |
| `/operaciones/moneda/:moneda` | operaciones-moneda | `OperacionMonedaView` | — | Sí |
| `/cuentas` | cuentas | `CuentasView` | — | Sí |
| `/clientes` | clientes | `ClientesView` | — | Sí |
| `/titulares` | titulares | `TitularesView` | — | Sí |
| `/bancos` | bancos | `BancosView` | — | Sí |
| `/tasas` | tasas | `TasasView` | — | Sí |
| `/dashboard` | dashboard | `DashboardView` | — | Sí |
| `/pool` | pool | `PoolView` | — | Sí |
| `/reportes` | reportes | `ReportesView` | — | Sí |
| `/comisiones` | comisiones | `ComisionesView` | — | Sí |
| `/usuarios` | usuarios | `UsuariosView` | — | Sí |
| `/:pathMatch(.*)*` | — | redirect a `/operaciones` | — | — |

### Guard
- `beforeEach`: redirige a `/login` si no hay token y la ruta no es pública
- Si hay token y la ruta es `/login`, redirige a `/operaciones`
