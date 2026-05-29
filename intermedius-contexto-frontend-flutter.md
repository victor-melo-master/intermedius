# Intermedius — Contexto Frontend Flutter
## Para: Johan Davila
## Fecha: Mayo 2026

---

## 1. Estado actual del frontend

La carpeta `/app` del repositorio tiene el scaffolding inicial de Flutter **sin ninguna pantalla implementada**. Es un proyecto Flutter limpio con la estructura de carpetas creada pero vacía de lógica de negocio.

**Lo que existe:**
- Estructura de carpetas Flutter estándar (lib/, android/, ios/, web/)
- pubspec.yaml con dependencias básicas
- Sin pantallas, sin navegación, sin conexión al API

**Lo que hay que construir:** todo.

---

## 2. API Base URL

```
https://intermedius.crececrm.com/api/v1
```

**Credenciales de prueba:**
- Email: `admin@test.com`
- Password: `password123`
- Rol: `super_admin` (ve todo)

---

## 3. Autenticación

### Login
```
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@test.com",
  "password": "password123"
}
```

**Respuesta:**
```json
{
  "token": "1|Jf76I9SSvYjnK6eUI...",
  "user": {
    "id": 1,
    "name": "Admin Principal",
    "email": "admin@test.com",
    "roles": ["super_admin"],
    "titular_id": null,
    "last_login_at": "2026-05-18T17:05:56.000000Z"
  }
}
```

### Uso del token en cada request
```
Authorization: Bearer TU_TOKEN_AQUI
```

### Logout
```
POST /api/v1/auth/logout
Authorization: Bearer TU_TOKEN_AQUI
```

### Usuario actual
```
GET /api/v1/auth/me
Authorization: Bearer TU_TOKEN_AQUI
```

---

## 4. Todos los endpoints disponibles

### Catálogos (CRUD completo)
```
GET    /api/v1/titulares
POST   /api/v1/titulares
GET    /api/v1/titulares/{id}
PUT    /api/v1/titulares/{id}
DELETE /api/v1/titulares/{id}

GET    /api/v1/bancos
POST   /api/v1/bancos
GET    /api/v1/bancos/{id}
PUT    /api/v1/bancos/{id}
DELETE /api/v1/bancos/{id}

GET    /api/v1/monedas
POST   /api/v1/monedas
GET    /api/v1/monedas/{id}
PUT    /api/v1/monedas/{id}
DELETE /api/v1/monedas/{id}

GET    /api/v1/cuentas
POST   /api/v1/cuentas
GET    /api/v1/cuentas/{id}
PUT    /api/v1/cuentas/{id}
DELETE /api/v1/cuentas/{id}

GET    /api/v1/clientes
POST   /api/v1/clientes
GET    /api/v1/clientes/{id}
PUT    /api/v1/clientes/{id}
DELETE /api/v1/clientes/{id}

GET    /api/v1/categorias-gasto
POST   /api/v1/categorias-gasto
GET    /api/v1/categorias-gasto/{id}
PUT    /api/v1/categorias-gasto/{id}
DELETE /api/v1/categorias-gasto/{id}
```

### Tasas
```
GET  /api/v1/configuracion/tasas-vigentes          # tasas del día activas
GET  /api/v1/configuracion/tasas-diarias           # lista con filtros
POST /api/v1/configuracion/tasas-diarias           # publicar nueva tasa (solo admin)
GET  /api/v1/configuracion/tasas-diarias/historial/{base}/{cotizada}
```

### Comisiones (solo admin)
```
GET/POST   /api/v1/configuracion/comisiones-cuenta
GET/POST   /api/v1/configuracion/comisiones-operador
GET/POST   /api/v1/configuracion/comisiones-metodo-pago
```

### Operaciones
```
GET    /api/v1/operaciones
POST   /api/v1/operaciones
GET    /api/v1/operaciones/{id}
PATCH  /api/v1/operaciones/{id}/verificar
DELETE /api/v1/operaciones/{id}
```

### Comisiones de una operación
```
GET   /api/v1/operaciones/{id}/comisiones
PATCH /api/v1/operaciones/{id}/comisiones/{comision_id}
```

### Reportes
```
GET  /api/v1/reportes/comisiones-operadores
POST /api/v1/reportes/comisiones-operadores/exportar
     Body: { "desde": "2026-05-01", "hasta": "2026-05-31", "formato": "excel" }
     # formato: "excel" o "pdf"
```

---

## 5. Roles y permisos — qué ve cada rol en el frontend

| Pantalla | super_admin | admin | operador | contador | lectura |
|---|---|---|---|---|---|
| Dashboard general | ✅ | ✅ | ✅ | ✅ | ✅ |
| Gestión de usuarios | ✅ | ❌ | ❌ | ❌ | ❌ |
| Catálogos (CRUD) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Publicar tasas del día | ✅ | ✅ | ❌ | ❌ | ❌ |
| Registrar operaciones | ✅ | ✅ | ✅ | ❌ | ❌ |
| Ver operaciones | ✅ | ✅ | ✅ (propias) | ✅ | ✅ |
| Verificar operaciones | ✅ | ✅ | ❌ | ✅ | ❌ |
| Ver comisiones | ✅ | ✅ | ❌ | ✅ | ❌ |
| Editar comisiones | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reportes y exports | ✅ | ✅ | ❌ | ✅ | ❌ |
| Configurar comisiones | ✅ | ✅ | ❌ | ❌ | ❌ |
| Bitácora/auditoría | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 6. Pantallas a implementar (prioridad sugerida)

### Prioridad 1 — Funcional básico
1. **Login** — formulario email/password, guardar token
2. **Dashboard general** — tasas del día, volumen, alertas
3. **Lista de operaciones** — con filtros por fecha, tipo, estatus
4. **Registrar operación** — formulario principal del operador
5. **Publicar tasa del día** — formulario admin (par, compra, venta)

### Prioridad 2 — Catálogos
6. **Cuentas** — lista y CRUD
7. **Clientes** — lista, búsqueda y CRUD
8. **Titulares** — lista y CRUD
9. **Bancos y monedas** — lista y CRUD

### Prioridad 3 — Gestión
10. **Detalle de operación** — con comisiones aplicadas
11. **Comisiones** — configuración y edición
12. **Reportes** — descarga Excel/PDF
13. **Usuarios** — gestión de usuarios y roles (solo super_admin)

### Prioridad 4 — Dashboards avanzados (cuando el API los tenga)
14. **Dashboard operativo** — saldos por cuenta en vivo
15. **Dashboard contable** — P&L, FIFO, cuadre

---

## 7. Flujo principal del operador (pantalla más importante)

El operador hace esto todos los días:

1. **Abre la app → Login**
2. **Ve el dashboard** → tasas del día publicadas por el admin
3. **Registra una operación:**
   - Selecciona tipo (compra USD, venta USD, cambio)
   - Selecciona cliente
   - Ingresa monto y tasa (el sistema sugiere la tasa del día)
   - El sistema valida que la tasa sea favorable a la casa
   - Si hay tasa favorable: confirma → el sistema calcula comisiones automáticamente
   - Si la tasa no es favorable: el sistema bloquea y muestra error
4. **Ve la operación registrada** con ganancia bruta, comisiones y ganancia neta

---

## 8. Estructura de respuestas del API

Todas las respuestas siguen este formato:

**Éxito lista:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

**Éxito objeto:**
```json
{
  "data": {
    "id": 1,
    "campo": "valor",
    ...
  }
}
```

**Error de validación (422):**
```json
{
  "message": "El campo tasa_venta es obligatorio.",
  "errors": {
    "tasa_venta": ["El campo tasa_venta es obligatorio."]
  }
}
```

**Error de autenticación (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Error de permisos (403):**
```json
{
  "message": "This action is unauthorized."
}
```

---

## 9. Recomendaciones técnicas para Flutter

### Dependencias sugeridas para pubspec.yaml
```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # HTTP y API
  dio: ^5.4.0                    # cliente HTTP con interceptors
  
  # Estado
  flutter_riverpod: ^2.5.0       # manejo de estado
  
  # Navegación
  go_router: ^13.0.0             # navegación declarativa
  
  # Storage local
  flutter_secure_storage: ^9.0.0  # guardar token de forma segura
  shared_preferences: ^2.2.0
  
  # UI
  fl_chart: ^0.67.0              # gráficas para dashboard
  intl: ^0.19.0                  # formateo de fechas y monedas
  
  # Utilidades
  freezed_annotation: ^2.4.0
  json_annotation: ^4.8.0

dev_dependencies:
  build_runner: ^2.4.0
  freezed: ^2.4.0
  json_serializable: ^6.7.0
```

### Interceptor para token (Dio)
```dart
// Agregar en cada request automáticamente
class AuthInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final token = SecureStorage.getToken(); // leer del storage seguro
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    super.onRequest(options, handler);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      // Token expirado: redirigir a login
      GoRouter.of(context).go('/login');
    }
    super.onError(err, handler);
  }
}
```

### Formateo de monedas venezolanas
```dart
// VES con separadores de miles y 2 decimales
String formatVES(double amount) {
  final formatter = NumberFormat('#,##0.00', 'es_VE');
  return 'Bs. ${formatter.format(amount)}';
}

// USD con símbolo
String formatUSD(double amount) {
  final formatter = NumberFormat('#,##0.00', 'en_US');
  return '\$ ${formatter.format(amount)}';
}
```

---

## 10. Lo que el API aún NO tiene (pendiente de backend)

Johan debe saber que estos módulos del backend aún no están implementados:

| Módulo | Estado | Fase |
|---|---|---|
| Integración tasas BCV (dolarapi.com) | ❌ Pendiente | Fase 3 |
| Integración Binance P2P | ❌ Pendiente | Fase 3 |
| Módulo de gastos | ❌ Pendiente | Fase 4-A |
| FIFO (costeo por lotes) | ❌ Pendiente | Fase 4-C |
| Dashboard general endpoint | ❌ Pendiente | Fase 5 |
| Dashboard operativo endpoint | ❌ Pendiente | Fase 5 |
| Dashboard contable endpoint | ❌ Pendiente | Fase 5 |
| Importador Excel histórico | ❌ Pendiente | Fase 7 |

**Recomendación:** Johan puede arrancar el frontend con los endpoints que YA existen (auth, catálogos, tasas, operaciones básicas) mientras el backend completa las fases pendientes en paralelo.

---

## 11. Probar el API antes de arrancar

Importar en Postman o Insomnia:

1. `POST https://intermedius.crececrm.com/api/v1/auth/login`
   - Body: `{"email":"admin@test.com","password":"password123"}`
   - Guardar el token de la respuesta

2. `GET https://intermedius.crececrm.com/api/v1/configuracion/tasas-vigentes`
   - Header: `Authorization: Bearer TU_TOKEN`
   - Debe retornar `{"data": []}`

3. `GET https://intermedius.crececrm.com/api/v1/monedas`
   - Header: `Authorization: Bearer TU_TOKEN`
   - Debe retornar la lista de monedas del sistema

Si los 3 responden correctamente, el API está listo para consumirse.
