# views/ — AI Context

## `LoginView`
- Ruta: `/login`
- Formulario: email + password
- Submit: `auth.login()` → si éxito, guarda token en localStorage, redirige a `/operaciones`
- Error: muestra mensaje de error (credenciales inválidas o usuario inactivo)

## `EmailVerifyView`
- Ruta: `/email/verify` (pública, sin auth)
- Lee `email` y `hash` de query params (`route.query.email`, `route.query.hash`)
- Llama `POST /api/v1/auth/verificar-email` con `{email, hash}`
- Estados: cargando → éxito (mensaje + link a login) → error (mensaje + opción de reenviar)
- Flujo completo: usuario recibe email → hace clic en link → se verifica automáticamente
- Si hash inválido o usuario no existe, muestra error

## `OperacionesView`
- Ruta: `/operaciones`
- Listado de operaciones con filtros: fecha_desde, fecha_hasta, tipo_codigo, estado
- Búsqueda por autocomplete de cliente
- Cards con: ID, fecha, tipo, cliente, montos USD/VES, estado, tasa, badge de verificación
- Crear nueva operación (botón → `/operaciones/nueva`)
- Navegación a detalle (click → `/operaciones/:id`)

## `OperacionFormView`
- Ruta: `/operaciones/nueva`
- Formulario de 4 pasos: tipo → moneda → cliente → montos
- Selectores: `TipoOperacionSelector`, `ClienteSelector`, `CuentaSelector` (origen y destino)
- Calculadora bidireccional integrada
- Resumen pre-confirmación con `ResumenOperacion`
- Submit: `operacionesStore.create(body)` → redirect a listado
- Soporte para registrar otra operación después de crear una

## `OperacionIntermediadaForm`
- Ruta: `/operaciones/nueva/intermediada`
- 4 movimientos: emisor USD → receptor VES (compra) + emisor VES → receptor USD (venta)
- Tasa compra / tasa venta / spread / ganancia estimada
- Dos pares de cuenta selector (origen/destino para cada movimiento)
- Submit: crea operación con 4 movimientos

## `OperacionDetailView`
- Ruta: `/operaciones/:id`
- Detalle completo: tipo, fecha, cliente, montos, tasa, movimientos, comisiones
- Botón "Verificar" (admin): `PATCH /operaciones/{id}/verificar`
- Edición de comisión inline: `ComisionToggle` + guardar
- `puedeEditar` computed: solo si está pendiente

## `OperacionMonedaView`
- Ruta: `/operaciones/moneda/:moneda`
- Listado filtrado por moneda (USD, VES, etc.)
- Cards: monto, cliente, fecha, tipo

## `CuentasView`
- Ruta: `/cuentas`
- CRUD de cuentas bancarias
- Formulario: tipo primero (efectivo oculta banco/nro cuenta)
- Selectores: ClienteSelector, banco, titular (inline creation), moneda
- Modal de saldo: carga manual de saldo vía `POST /cuentas/{id}/saldo`

## `ClientesView`
- Ruta: `/clientes`
- CRUD + soft-delete
- Toggle Activos / Papelera (mostrarPapelera ref)
- Lista con: nombre, alias, teléfono, saldo, badge eliminado/inactivo
- Botón Recuperar en lista (admin)
- Modal detalle: info, cuentas bancarias, historial transacciones, export PDF
- Modal crear/editar cliente (nombre, alias, teléfono, email, notas)
- Modal agregar cuenta para cliente (tipo primero, efectivo sin banco)

## `TitularesView`
- Ruta: `/titulares`
- CRUD de titulares (cuentahabientes)
- Modal crear/editar: nombre, alias, activo

## `BancosView`
- Ruta: `/bancos`
- CRUD de bancos
- Modal: nombre, código, país

## `TasasView`
- Ruta: `/tasas`
- Gestión de tasas de mercado
- Selector de par de monedas (base → cotizada)
- Publicar nueva tasa: monto, fuente, vigencia
- Historial de tasas publicadas
- Tasas automáticas de referencia (BCV, paralelo, Binance)

## `DashboardView`
- Ruta: `/dashboard`
- Resumen general: saldo USD/VES, operaciones del día
- Tasas de referencia en tiempo real
- Auto-refresh cada 30s
- `refStale`: warning si datos tienen más de 5 minutos

## `PoolView`
- Ruta: `/pool`
- Pool de pagadores: tabs (Disponibles / Mis Órdenes)
- Pagar: modal con selector de banco, titular, número de cuenta
- Copiar info al portapapeles
- Auto-refresh cada 15s

## `ReportesView`
- Ruta: `/reportes`
- Filtros: fecha desde/hasta, tipo reporte
- Exportar PDF y Excel

## `ComisionesView`
- Ruta: `/comisiones`
- Gestión de comisiones por operador
- CRUD de comisiones: operador, tipo, monto/porcentaje, vigencia
- Desactivar comisión (vigente_hasta)

## `UsuariosView`
- Ruta: `/usuarios`
- CRUD de usuarios del sistema
- Roles: selector de roles (admin, operador, contador, lectura, pagador)
- Toggle activo/inactivo
- `roleBadgeClass`: color-coded badges por rol
