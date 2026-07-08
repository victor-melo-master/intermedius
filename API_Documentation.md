# Intermedius API - Documentación del Sistema

## Descripción General

El API Intermedius es una aplicación web Laravel que sirve como sistema principal para una **casa de cambio (casa de conversión de divisas)**. Gestiona operaciones financieras, tipos de cambio, clientes, titulares, cuentas y comisiones.

## Estructura General

### Directorios Principales

```
api/app/
├── Controllers/
│   ├── Api/V1/           # API versión 1
│   ├── Models/           # Modelos Eloquent de Laravel
│   ├── Services/         # Servicios de aplicación
│   ├── Jobs/             # Trabajos en segundo plano
│   ├── Exports/          # Exportaciones de archivos (Excel/PDF)
│   └── Requests/         # Validación de solicitudes HTTP
├── Models/               # Modelos de datos principales
├── Policies/             # Políticas de autorización
└── Providers/            # Proveedores de servicios
```

## Modelos de Datos Principales

### 1. User
- **Propósito**: Autenticación de usuarios y roles
- **Relaciones clave**: `titular()` (pertence a un titular)
- ** Campos importantes**: `name`, `email`, `password`, `activo`, `roles`
- **Permisos actuales asignados en `AppServiceProvider`**:
  - Titular, Banco, Moneda, Cuenta, Cliente, CategoriaGasto, Operacion

### 2. Banco (Banco)
- **Propósito**: Datos de bancos y entidades financieras
- **Campos**: `nombre`, `codigo`, `pais`, `activo`
- **Relaciones**: Tiene muchas `cuentas()`

### 3. Cliente
- **Propósito**: Clientes de la casa de cambio
- **Campos**: `nombre`, `alias`, `documento`, `telefono`, `email`, `notas`
- **Relaciones**: Tiene muchas `cuentas()`

### 4. Cuenta
- **Propósito**: Cuentas bancarias para titulares o clientes
- **Campos clave**: `titular_id` / `cliente_id`, `banco_id`, `moneda_id`, `alias`
- **Regla de negocio**: Exclusiva - una cuenta pertenece **EITHER** a un titular **OR** a un cliente (no ambos)
- **Relaciones**: Pertenece a un `titular()` y/o `cliente()`

### 5. Moneda
- **Propósito**: Monedas soportadas para conversión
- **Campos**: `codigo`, `nombre`, `simbolo`, `es_fiat`, `es_cripto`, `decimales`
- **Relaciones**: Tiene muchas `cuentas()`

### 6. ComisionCuenta
- **Propósito**: Comisiones aplicables por cuenta
- **Campos**: `cuenta_id`, `banco_id`, `descripcion`, `tipo_calculo`, `valor`
- **Campos de tiempo**: `vigente_desde`, `vigente_hasta`
- **Regla de negocio**: Requiere al menos `cuenta_id` o `banco_id`

### 7. Operacion
- **Propósito**: Transacciones principales (depósitos, retiros, conversiones)
- **Campos clave**: `tipo_operacion_id`, `cliente_id`, `operador_id`, `estatus`

## Módulo de Controladores

### Autenticación
- **Archivo**: `AuthController.php`
- **Endpoints**: `POST /v1/auth/login`, `POST /v1/auth/logout`, `GET /v1/auth/me`

### API V1 (Versión 1)
- **Prefijo**: `v1`
- **Middleware**: `auth:sanctum` (excepto `/login`)
- **Autorización**: Usando políticas Laravel + roles Spatie

#### Controladores Principales en /v1

1. **DashboardController**
   - `general`: Resumen general
   - `tasasReferencia`: Tasas de referencia históricas
   - `resumen`: Resumen detallado

2. **OperacionController**
   - Maneja operaciones contables (LEDGER, registrar/depositar dinar)
   - `verificar`: Verifica operaciones

3. **UserController**
   - CRUD completo para usuarios con asignación de roles

4. **GastoController**
   - Maneja gastos (SUBTYPE de operaciones)

5. **PoolController**
   - Gestiona pool de pagadores/conversores activos

#### Configuración (escritura solo admin)

1. **ComisionCuentaController**
2. **ComisionOperadorController**
3. **ComisionMetodoPagoController**
4. **ComisionOperacionController**
5. **TasaDiariaController**

#### Catálogos Públicos
- **BancoController**, **MonedaController**, **CuentaController**

## Servicios de Aplicación

### Services/App

1. **CalculadorComisionesService**
   - Calcula comisiones basadas en reglas

2. **TasaDiariaService**
   - Gestiona tasas diarias (actualización, histórico)

3. **ReporteComisionesOperadoresService**
   - Genera reportes de comisiones para operadores

4. **RegistroOperacionService**
   - coordina el registro de operaciones

### Operaciones/ (Registro de Operaciones)

1. **RegistroOperacionService**
   - Registra todas las operaciones (depósitos, retiros, conversiones)
   - Puede manejar fondos FIFO (primero en entrar, primero en salir)
   - Calcula automáticamente valores y actualiza saldos

## Trabajos en Segundo Plano

### Jobs/

1. **SincronizarTasasJob**
   - Sincroniza tasas de referencia externas

2. **SincronizarTasasReferenciaJob**
   - Sincroniza tasas de referencia históricas

3. **AlertarTasasFaltantesJob**
   - Alerta si faltan tasas de mercado

4. **RecalcularSaldoCuentaJob**
   - Recalcula saldos de cuentas de forma incremental

5. **ProcesarFifoOperacionJob**
   - Procesa fondos FIFO para mejoras de rendimiento

6. **GenerarReporteMensualComisionesJob**
   - Genera reportes de comisiones mensuales

## Exportaciones de Archivos

### Exports/

1. **ComisionesDetalleSheet**
2. **ComisionesOperadoresExport**
3. **ComisionesResumenSheet**
   - Generan exportaciones de Excel para reportes de comisiones

## Flujo de Trabajo Principal

### 1. Autenticación & Autorización

```
Cliente -> Sanctum Token -> Roles (super_admin, admin, operador, contador, lectura)
```

### 2. Registro de Operaciones

1. Usuario crea operación (POST /v1/operaciones)
2. `RegistroOperacionService.registrar()` procesa:
   - Valida duplicados/contracciones con fondos FIFO
   - Crea registros contables en `movimientos`
   - Aplica comisiones relevantes por cuenta
   - Actualiza saldos
3. Si verificada, transición de estado + auditoría

### 3. Flujo de Fondo (FIFO)

El sistema soporta FIFO (primero en entrar, primero en salir) para multi-divisa:
- Fondos entrantes agrupados por moneda
- Mejoras de rendimiento disponibles con `ProcesarFifoOperacionJob`

### 4. Control de Comisiones

- Comisiones por cuenta aplicables por fecha (`vigente_desde` / `vigente_hasta`)
- Diferentes tipos (fijo, porcentaje, líquido, bruto)

### 5. Pool de Pagadores

- 5% de retiros líquidos asignados aleatoriamente
- Los titulares pueden tomar/soltar operaciones

## Rutas de API V1

```
GET  /v1/dashboard/general             - Panel general
GET  /v1/dashboard/tasas-referencia    - Referencias históricas de tasas
GET  /v1/dashboard/resumen             - Resumen detallado

GET  /v1/titulares                   - Catálogo de titulares
GET  /v1/bancos                      - Catálogo de bancos
GET  /v1/monedas                     - Catálogo de monedas
GET  /v1/cuentas                     - Catálogo de cuentas
GET  /v1/clientes                    - Catálogo de clientes

GET  /v1/tasas/actuales             - Tasas actuales del mercado
GET  /v1/tasas/historico            - Histórico de tasas

GET  /v1/gastos                      - Lista de gastos
POST /v1/gastos                      - Crea gasto (asiento contable)

GET  /v1/configuracion/tasas-vigentes - Tasas diarias vigentes (lectura pública)
GET  /v1/configuracion/tasas-diarias  - Catálogo de tasas diarias (admin)

GET  /v1/operaciones                 - Histórico de operaciones
POST /v1/operaciones                 - Crea operación
PATCH /v1/operaciones/{operacion}/verificar - Verifica operación

GET  /v1/pool/                      - Pool de pagadores (admin|pagador|operador|super_admin)

GET  /v1/usuarios                    - CRUD de usuarios (admin|super_admin)

GET  /v1/reportes/comisiones-operadores - Reporte de comisiones (admin|super_admin|contador)
POST /v1/reportes/comisiones-operadores/exportar - Exportar reporte

GET  /v1/admin/bitacora              - Auditoría (super_admin)
```

## Configuración y Complemetos

### Configuración (en `config/app.php`)
- **Nombre**: Laravel (personalizar en `.env`)
- **Timezone**: UTC

### Archivos ENV
- `.env`: Variables de entorno
- `.env.example`: Plantilla
- `config/*`: Configuración de servicios

## Documentación de Base de Datos

Ver `intermedius_casa_cambio.sql` para migraciones y estructura.

## Códigos de Estatus

### Operaciones
- `registrada`: Nueva
- `verificado`: Verificada

### Comisiones
- `activa`, `inactiva`

### Usuarios
- `activo`, `inactivo`

## Rol y Autorización

### Roles Spatie (usuarios)
- `super_admin`: Todos los privilegios
- `admin`: Administración completa (excepto auditoría)
- `operador`: Puede crear operaciones
- `contador`: Puede verificar operaciones y ver reportes
- `lectura`: Solo lectura en catálogo

### Privilegios (middleware)
- `role:admin|super_admin` → Escritura
- `role:super_admin` → Auditoría, escritura configuración
- `role:pagador|admin|super_admin` → Pool de pagadores

## Referencias de Entorno

### Errores Comunes / Solución de Problemas

### Mejoras Futuras Sugeridas

1. Mantenimiento multi-divisa (pool de pagadores)
2. Implementar mejorías FIFO de rendimiento
3. Agregar FX rates externos (API)
4. Alertas en tiempo real para conversaciones
5. Integración de kerning de Forex (externo)

## Importante Notas

- Esta es una **API solo de backend** - Rust para el procesamiento de pagos
- En producción, debe estar detrás de proxy inverso (CDN + WAF)
- Se recomienda usar Rate Limiting y JWT por seguridad
- Las tasas de mercado se obtienen de `tasas/actuales` endpoint
- Los retiros son asignados aleatoriamente al pool de pagadores (5%)
