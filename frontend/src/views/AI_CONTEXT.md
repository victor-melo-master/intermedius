# views/ — AI Context

> 19 vistas/páginas organizadas en 7 subdirectorios. Cada vista es un componente Vue completo con `<script setup>`.

## Estructura

```
views/
├── auth/
│   ├── LoginView.vue               → /login
│   └── EmailVerifyView.vue         → /email/verify
│
├── operaciones/
│   ├── OperacionesView.vue         → /operaciones
│   ├── OperacionFormView.vue       → /operaciones/nueva
│   ├── OperacionIntermediadaForm.vue → /operaciones/nueva/intermediada
│   ├── OperacionDetailView.vue     → /operaciones/:id
│   └── OperacionMonedaView.vue     → /operaciones/moneda/:moneda
│
├── catalogos/
│   ├── ClientesView.vue            → /clientes
│   ├── CuentasView.vue             → /cuentas
│   ├── TitularesView.vue           → /titulares
│   ├── BancosView.vue              → /bancos
│   └── UsuariosView.vue            → /usuarios
│
├── configuracion/
│   ├── TasasView.vue               → /tasas
│   └── ComisionesView.vue          → /comisiones
│
├── dashboard/
│   └── DashboardView.vue           → /dashboard
│
├── pool/
│   └── PoolView.vue                → /pool
│
├── reportes/
│   └── ReportesView.vue            → /reportes
│
└── NotFoundView.vue                → 404
```

---

## auth/

### LoginView (`/login`)
- Formulario email + password
- Submit: `auth.login()` → redirect a `/operaciones`
- Error: muestra mensaje de credenciales inválidas o usuario inactivo

### EmailVerifyView (`/email/verify`)
- Ruta **pública** (sin auth)
- Lee `email` y `hash` de query params
- Llama `POST /api/v1/auth/verificar-email` con `{email, hash}`
- Estados: cargando → éxito (link a login) → error (opción de reenviar)

---

## operaciones/

### OperacionesView (`/operaciones`)
- Listado de operaciones con filtros: tipo_codigo, estatus, fecha_desde, fecha_hasta, moneda, cliente (autocomplete)
- Cards con: ID, fecha, tipo, cliente, montos USD/VES, estado, tasa, badge de verificación
- Botón "Nueva" → `/operaciones/nueva`
- Click en card → `/operaciones/:id`
- Modal de filtros con botones "Aplicar" / "Limpiar"

### OperacionFormView (`/operaciones/nueva`)
- Formulario principal de operación (compra/venta)
- **Sub-componentes**: `OperacionFormCabecera`, `CalculadoraBidireccional`, `OperacionFormTransacciones`, `OperacionFormResumen`
- **Composables usados**: `useTasas`, `useAuth`, `useOperaciones`, `useCuentas`, `useTitulares`, `useNotification`
- **Flujo**: seleccionar tipo → cliente → monto → distribuir en transacciones → confirmar
- **Soporte edición**: si `route.params.id` existe, carga operación existente
- **Moneda**: determinada por `route.params.moneda` (default USD), quote es la otra moneda del par
- Submit: construye movimientos (cuenta origen negativo, cuenta destino positivo) → `operaciones.create(body)`
- Éxito: muestra opciones "Registrar otra" / "Ir al Pool de Pagadores"

### OperacionIntermediadaForm (`/operaciones/nueva/intermediada`)
- Formulario de operación intermediada (comprador + vendedor)
- 4 cuentas: emisor divisa, emisor VES, receptor divisa, receptor VES
- Tasas: compra (al emisor) y venta (al receptor) con spread
- Ganancia estimada calculada
- Submit: crea operación con 4 movimientos

### OperacionDetailView (`/operaciones/:id`)
- Detalle completo: tipo, fecha, cliente, montos, tasa, movimientos (tabla), métricas
- Botón "Verificar" (admin): `PATCH /operaciones/:id/verificar`
- Botón "Editar" (si no verificado): abre modal de motivo → redirect a edición
- `puedeEditar` computed: solo si no está verificado ni cancelado

### OperacionMonedaView (`/operaciones/moneda/:moneda`)
- Selector de moneda para nueva operación
- Muestra tarjetas con monedas disponibles (USD, USDT, EUR, COP)
- Click → `/operaciones/nueva` con moneda seleccionada

---

## catalogos/

### ClientesView (`/clientes`)
- CRUD + soft-delete
- Toggle "Activos / Papelera" en header
- Lista con: nombre, alias, teléfono, saldo, badge eliminado/inactivo
- Modal detalle: info, cuentas bancarias, historial transacciones
- Modal crear/editar: nombre, alias, teléfono, email, notas
- Botón "Recuperar" en lista (admin)
- Soft-delete: `fetchAll()` o `fetchTrashed()` según `mostrarPapelera`

### CuentasView (`/cuentas`)
- CRUD de cuentas bancarias
- Filtros por cliente y moneda (selects + botón Limpiar) → re-consulta `GET /cuentas` con `cliente_id`/`moneda_id`
- Selector de tipo al inicio (efectivo oculta banco/número de cuenta)
- Selectores: ClienteSelector, banco, titular, moneda
- Modal de saldo: carga manual de saldo

### TitularesView (`/titulares`)
- CRUD de titulares (cuentahabientes)
- Modal crear/editar: nombre, alias, teléfono, email, activo

### BancosView (`/bancos`)
- CRUD de bancos
- Modal: nombre, código, país

### UsuariosView (`/usuarios`)
- CRUD de usuarios del sistema
- Roles: super_admin, admin, operador, contador, lectura
- Toggle activo/inactivo
- Selector de titular asociado (opcional)
- `roleBadgeClass`: badges color-coded por rol

---

## configuracion/

### TasasView (`/tasas`)
- Gestión de tasas de mercado del día
- Selector de par de monedas (base → cotizada)
- Publicar nueva tasa: monto, fuente, vigencia
- Historial de tasas publicadas
- Tasas agrupadas por moneda base con iconos
- Solo admin puede publicar/editar

### ComisionesView (`/comisiones`)
- CRUD de comisiones por método de pago
- Tipos: porcentaje o monto fijo
- Asociadas a moneda y cuenta (opcional)
- Fechas de vigencia (desde/hasta)
- Desactivar comisión (delete)

---

## dashboard/

### DashboardView (`/dashboard`)
- Resumen general: saldo USD/VES, operaciones del día
- Tasas de referencia en tiempo real
- Auto-refresh cada 30s
- `refStale`: warning si datos > 5 minutos

---

## pool/

### PoolView (`/pool`)
- Pool de pagadores: tabs (Disponibles / Mis Órdenes)
- Cada orden: ID, cliente, monto, moneda, tasa, timer de expiración
- Pagar: modal con selector de banco, titular, número de cuenta
- Auto-refresh cada 15s
- Acciones: tomar, soltar, pagar, cancelar

---

## reportes/

### ReportesView (`/reportes`)
- Filtros: fecha desde/hasta
- Consultar comisiones por operador
- Exportar a Excel
- Muestra resultados en cards con nombre, cantidad ops, monto total

---

## NotFoundView
- Página 404 con botón "Volver al inicio" (`/dashboard`)
