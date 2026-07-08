# views/ — Vistas de la aplicación

16 vistas, cada una corresponde a una ruta.

| Vista | Ruta | Propósito |
|---|---|---|
| `LoginView` | `/login` | Pantalla de inicio de sesión |
| `OperacionesView` | `/operaciones` | Listado de operaciones con filtros |
| `OperacionFormView` | `/operaciones/nueva` | Crear operación (depósito/retiro) |
| `OperacionIntermediadaForm` | `/operaciones/nueva/intermediada` | Crear operación intermediada (4 movimientos) |
| `OperacionDetailView` | `/operaciones/:id` | Detalle + verificar + editar comisión |
| `OperacionMonedaView` | `/operaciones/moneda/:moneda` | Operaciones filtradas por moneda |
| `CuentasView` | `/cuentas` | CRUD cuentas, tipo primero, ocultar efectivo |
| `ClientesView` | `/clientes` | CRUD + soft-delete + papelera + historial + PDF |
| `TitularesView` | `/titulares` | CRUD titulares |
| `BancosView` | `/bancos` | CRUD bancos |
| `TasasView` | `/tasas` | Gestión de tasas de mercado |
| `DashboardView` | `/dashboard` | Dashboard con resúmenes y tasas |
| `PoolView` | `/pool` | Pool de pagadores (tomar, soltar, pagar) |
| `ReportesView` | `/reportes` | Reportes descargables (PDF/Excel) |
| `ComisionesView` | `/comisiones` | Gestión de comisiones por operador |
| `UsuariosView` | `/usuarios` | CRUD usuarios del sistema |
