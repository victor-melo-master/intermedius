# AUDITORÍA TÉCNICA — INTERMEDIUS GROUP v3.0
## Informe corregido del CTO de Tecnología
**Fecha:** Mayo 2026 | **Clasificación:** Confidencial — Uso interno
**Alcance:** Backend Laravel 11, Frontend Vue 3 (migrado desde Flutter), Infraestructura, Contexto de negocio

---

## 1. HALLAZGO CRÍTICO: MIGRACIÓN FLUTTER → VUE

### Estado real
- **Flutter** fue implementado en `/app/lib/` con pantallas completas (Operaciones, Tasas, Clientes, Cuentas, Reportes) pero **nunca se desplegó**.
- **Vue 3** fue construido en `/frontend/` como reemplazo directo, priorizando responsividad web y facilidad de despliegue.
- **Decisión tomada:** El frontend activo y desplegado es **Vue 3** (`admin.intermediusg.com`). Flutter queda como código fuente histórico en `/app/`, pero **no es el frontend operativo**.
- **Implicación:** Todos los esfuerzos de frontend deben centrarse en Vue. Flutter solo se reactivaría si se requiere una app nativa móvil (APK/iOS) en el futuro.

---

## 2. ESTADO REAL DEL PROYECTO (vs. Plan de Fases)

| Fase | Descripción | Estado real | Riesgo |
|---|---|---|---|
| 1 | Maestros y autenticación | ✅ Completa | Bajo |
| 2 | Ledger de operaciones y movimientos | ✅ Completa | Bajo |
| 2.5 | Configuración (tasas, comisiones, bitácora, reportes) | ✅ Completa | Bajo |
| 3 | Integración BCV/Binance (tasas de mercado) | ❌ Pendiente | Medio |
| 4-A | Módulo de gastos | ⚠️ Parcial (endpoint existe, sin UI en Vue) | Medio |
| 4-C | FIFO — costeo por lotes | ❌ Pendiente | Alto |
| 5 | Dashboards avanzados | ⚠️ Parcial (Vue solo muestra tasas vigentes) | Medio |
| 6 | Pulido, auditoría, hardening | 🔄 En curso | Alto |
| 7 | Importador desde Excel histórico | ❌ Pendiente | Alto (bloquea switchover) |
| 8 | Switchover (cliente deja Excel) | ❌ Bloqueado por Fase 7 | Crítico |

> **Backend ~65% completo.** Frontend Vue ~50% de la especificación oficial.

---

## 3. AUDITORÍA DEL BACKEND (Laravel 11)

### 3.1 Arquitectura — Estado general: ✅ Sólida

- `Controllers/Api/V1/` ✅
- `Services/Operaciones/`, `Services/Configuracion/` ✅
- `Models/` con relaciones typed ✅
- `Jobs/` asíncronos ✅
- `Policies/` ✅
- `Requests/` ✅
- `routes/api.php` con versionado y middleware ✅

### 3.2 Desviaciones críticas

**A. Inconsistencia `tipo_codigo` vs `tipo_operacion_id`**
- **Backend espera:** `tipo_codigo: string` (ej: `"venta_usd"`) — `StoreOperacionRequest` + `RegistroOperacionService`
- **Especificación frontend (Pantalla 3.3):** `tipo_operacion_id: 1` (int)
- **Vue actual envía:** `tipo_operacion_id: Number` (int)
- **Impacto:** 🔴 **Cualquier frontend actual falla con 422 al crear operaciones.**

**B. Payload del Vue es incompleto para el backend**
Backend requiere: `fecha`, `tipo_codigo`, `operador_id`, `movimientos[]` (min 2 items con `cuenta_id`, `monto`, `tasa_a_usd`).
Vue envía: solo `tipo_operacion_id`, `cliente_id`, `tasa_aplicada`, `referencia`, `descripcion`.

**C. `moneda_id` en movimientos es redundante**
- Especificación manda `moneda_id` en cada movimiento.
- Backend lo infiere desde la cuenta. No rompe nada, pero genera confusión.

### 3.3 Lógica de Negocio — Ledger

| Riesgo | Severidad | Justificación |
|---|---|---|
| FIFO pendiente (Fase 4-C) | 🔴 Alto | Sin FIFO no hay costeo real de inventario de divisas. El contexto dice "la parte más compleja". |
| `case 'cambio':` retorna `$0` ganancia | 🟡 Medio | Tipo principal documentado pero cálculo en TODO. |
| `tasa_mercado_snapshot` referencial | 🟢 Bajo | Diseño correcto: BCV/Binance solo para comparación del admin. |
| Comisiones snapshot | 🟢 Bajo | Una vez aplicadas no cambian automáticamente. Admin edita con razón. |

### 3.4 Seguridad — Hallazgos CRÍTICOS

| # | Hallazgo | Ubicación | Impacto |
|---|---|---|---|
| 1 | **DB_PASSWORD expuesta** | `intermedius-contexto-proyecto.md` línea 43: `MSlx0^k64vop!2025` | Cualquier persona con repo tiene password de producción |
| 2 | **Admin password expuesta** | `intermedius-contexto-proyecto.md` línea 47: `admin@test.com` / `password123` | Compromiso inmediato si no se cambió |
| 3 | **Sin rate limiting** | No existe en `api.php` ni middleware | Fuerza bruta en login |
| 4 | **Token en localStorage** | `frontend/src/api/axios.js` línea 13 | XSS roba token fácilmente |
| 5 | **Sin 2FA** | No implementado | Cumplimiento bancario incumplido |
| 6 | **Sin CSP / HSTS / X-Frame-Options** | Headers de seguridad no configurados | Clickjacking, XSS |
| 7 | **CORS con wildcard implícito** | Solo dominios específicos, pero sin verificación de origin estricta | Medio |
| 8 | **Sin cifrado de datos sensibles** | Clientes, cuentas bancarias en texto plano | GDPR/ISO 27001 incumplido |
| 9 | **Sin logging de intentos fallidos** | No se registra login fallido | Sin detección de intrusión |

### 3.5 Cumplimiento ISO 27001 / AML / UIF

- **ISO 27001:** ~25-30% de controles esenciales implementados.
- **AML/UIF Venezuela:** ❌ Sin KYC, sin reportes de operaciones sospechosas, sin umbrales. **Riesgo regulatorio alto.**
- **Privacidad:** ❌ Sin política de privacidad visible.

---

## 4. AUDITORÍA DEL FRONTEND VUE 3 (Estado actual)

### 4.1 Inventario de archivos

```
frontend/src/
├── App.vue                 (router-view limpio)
├── api/axios.js            (cliente API con interceptores)
├── components/AppShell.vue (layout sidebar + header responsive)
├── router/index.js         (8 rutas protegidas + login)
├── stores/
│   ├── auth.js             (login/logout/init, roles)
│   ├── operaciones.js      (CRUD + verificar)
│   ├── clientes.js         (listar/buscar/crear/actualizar)
│   └── tasas.js            (vigentes/historial/monedas/publicar)
└── views/
    ├── LoginView.vue         ✅ Funcional
    ├── DashboardView.vue     ⚠️ Solo tasas vigentes + bienvenida
    ├── OperacionesView.vue   ✅ Lista + filtro por estatus
    ├── OperacionDetailView.vue ⚠️ Detalle básico (sin movimientos ni comisiones)
    ├── OperacionFormView.vue   🔴 ROTO (payload incompatible)
    ├── ClientesView.vue        ✅ Lista + búsqueda + crear
    ├── TasasView.vue           ✅ Listar + publicar (admin)
    ├── CuentasView.vue         ⚠️ Solo listar (sin CRUD completo)
    └── ReportesView.vue        ⚠️ Consultar comisiones + exportar stub
```

### 4.2 Matriz de funcionalidades vs. Especificación oficial

| Requisito especificación (`intermedius-funcionalidades-pantallas.md`) | Estado Vue actual | Coincide |
|---|---|---|
| **Login** con roles | ✅ Implementado | Sí |
| **Dashboard:** tasas BCV/Binance referenciales | ❌ No existe | No |
| **Dashboard:** resumen del día (operaciones hoy, USD movidos, ganancia bruta/neta) | ❌ No existe | No |
| **Dashboard:** alertas (sin_verificar, sin_tasa_referencia) | ❌ No existe | No |
| **Lista operaciones:** filtros, estados, ganancia | ✅ Implementado | Sí |
| **Detalle operación:** tabla de movimientos contables | ❌ No existe | No |
| **Detalle operación:** sección comisiones aplicadas | ❌ No existe | No |
| **Detalle operación:** botón editar comisión (admin) | ❌ No existe | No |
| **Detalle operación:** botón eliminar (admin) | ❌ No existe | No |
| **Registrar operación:** stepper 5 pasos (tipo, datos, monto/tasa, cuentas, resumen) | ❌ Formulario simple de 1 paso | No |
| **Registrar operación:** validación de tasa favorable a la casa | ❌ No implementada | No |
| **Registrar operación:** selector de cuentas con saldo | ❌ No existe | No |
| **Registrar operación:** preview de comisiones y ganancia estimada | ❌ No existe | No |
| **Clientes:** buscar, ver detalle, editar | ⚠️ Buscar y crear. Sin detalle ni editar. | Parcial |
| **Cuentas:** listar agrupado por titular, semáforo saldo, crear/editar | ⚠️ Solo listar plano. Sin crear/editar. | Parcial |
| **Titulares, Bancos, Monedas, Categorías de gasto** | ❌ No implementado | No |
| **Configuración tasas:** historial por par de monedas | ❌ No implementado | No |
| **Configuración comisiones:** por cuenta/operador/método de pago | ❌ No implementado | No |
| **Reportes:** exportar Excel/PDF funcional | ⚠️ Botón existe pero usa alert() stub | Parcial |
| **Gestión de usuarios** | ❌ No implementado | No |
| **Formato monedas:** VES separadores, USD "$", USDT "₮" | ⚠️ USD formateado, VES/USDT no diferenciados | Parcial |
| **Responsive móvil/tablet/desktop** | ✅ Tailwind responsive | Sí |

**Cobertura funcional:** ~50% de la especificación oficial. El Vue es un **MVP operable para demo** pero **no para producción según requerimientos documentados**.

### 4.3 Fallo crítico: `OperacionFormView.vue`

**Código actual (`submit`):**
```javascript
const body = {
  tipo_operacion_id: Number(form.tipo_operacion_id),  // ❌ Backend espera tipo_codigo (string)
  tasa_aplicada: parseFloat(form.tasa_aplicada),
}
if (form.cliente_id) body.cliente_id = Number(form.cliente_id)
if (form.referencia) body.referencia = form.referencia
if (form.descripcion) body.descripcion = form.descripcion
// ❌ Faltan: fecha, operador_id, movimientos[]
```

**Backend espera (`StoreOperacionRequest`):**
```php
[
  'fecha' => ['required', 'date'],
  'tipo_codigo' => ['required', 'string', 'exists:tipos_operacion,codigo'],
  'operador_id' => ['required', 'integer', 'exists:users,id'],
  'movimientos' => ['required', 'array', 'min:1'],
  'movimientos.*.cuenta_id' => ['required', 'integer', 'exists:cuentas,id'],
  'movimientos.*.monto' => ['required', 'numeric', 'not_in:0'],
  'movimientos.*.tasa_a_usd' => ['required', 'numeric', 'gt:0'],
]
```

**Resultado:** Al presionar "Registrar operación", el backend responde **422 Unprocessable Entity** con múltiples errores de validación. **Ninguna operación puede crearse desde el frontend Vue actual.**

### 4.4 Arquitectura frontend — Fortalezas y debilidades

**Fortalezas:**
- Vue 3 Composition API + `<script setup>` — código moderno y limpio.
- Pinia stores con Composition API pattern.
- TailwindCSS para responsive.
- Router con lazy loading y guards de autenticación.
- Axios interceptores para token y 401 redirect.
- AppShell responsive con drawer móvil.

**Debilidades:**
- **Token en localStorage** (`api/axios.js:13`) — vulnerable a XSS. Debería usar httpOnly cookies.
- **Sin manejo global de errores** — cada componente repite `err.response?.data?.message`.
- **Sin composables reutilizables** — lógica de fetch duplicada en cada store.
- **Sin tests** — cero cobertura de tests en frontend.
- **CuentasView.vue no usa store** — hace fetch directo con `api.get('/cuentas')` en vez de un store consistente.
- **Reportes exportar es un stub** — muestra `alert('Reporte generado. Revisa tu email o el servidor.')` en vez de descargar archivo.

---

## 5. AUDITORÍA DE API-FRONTEND ALINEACIÓN

### 5.1 Endpoints consumidos por Vue (actual)

| Endpoint | Método | Uso en Vue | Estado |
|---|---|---|---|
| `/auth/login` | POST | LoginView | ✅ Funciona |
| `/auth/me` | GET | auth.init() | ✅ Funciona |
| `/auth/logout` | POST | auth.logout() | ✅ Funciona |
| `/operaciones` | GET | OperacionesView | ✅ Funciona |
| `/operaciones` | POST | OperacionFormView | 🔴 **Roto** (payload incompatible) |
| `/operaciones/{id}` | GET | OperacionDetailView | ✅ Funciona |
| `/operaciones/{id}/verificar` | PATCH | OperacionDetailView | ✅ Funciona |
| `/clientes` | GET/POST | ClientesView | ✅ Funciona |
| `/cuentas` | GET | CuentasView | ✅ Funciona |
| `/configuracion/tasas-vigentes` | GET | Dashboard + Tasas | ✅ Funciona |
| `/configuracion/tasas-diarias` | POST | TasasView (admin) | ✅ Funciona |
| `/monedas` | GET | TasasView | ✅ Funciona |
| `/reportes/comisiones-operadores` | GET | ReportesView | ⚠️ No verificado en producción |
| `/reportes/comisiones-operadores/exportar` | POST | ReportesView | ⚠️ Stub, no descarga archivo |

### 5.2 Endpoints existentes en backend pero NO consumidos por Vue

| Endpoint | Razón de no uso | Impacto |
|---|---|---|
| `GET /configuracion/tasas-diarias` (historial) | Sin vista de historial de tasas | Media |
| `GET /configuracion/tasas-diarias/historial/{base}/{cotizada}` | Sin vista de historial | Media |
| `GET/POST /configuracion/comisiones-cuenta` | Sin módulo de comisiones | Alta |
| `GET/POST /configuracion/comisiones-operador` | Sin módulo de comisiones | Alta |
| `GET/POST /configuracion/comisiones-metodo-pago` | Sin módulo de comisiones | Alta |
| `GET/POST /titulares` | Sin catálogo de titulares | Media |
| `GET/POST /bancos` | Sin catálogo de bancos | Media |
| `GET/POST /categorias-gasto` | Sin módulo de gastos | Alta |
| `GET/PUT/DELETE /clientes/{id}` | ClientesView solo lista y crea | Media |
| `GET/PUT/DELETE /cuentas/{id}` | CuentasView solo lista | Media |
| `GET /operaciones/{id}/comisiones` | Sin sección de comisiones en detalle | Media |
| `PATCH /operaciones/{id}/comisiones/{comision_id}` | Sin editar comisiones | Media |
| `POST /operaciones/{id}` (update) | Sin editar operaciones | Media |

---

## 6. INFRAESTRUCTURA Y DEPLOY

| Aspecto | Documentado | Estado real | Riesgo |
|---|---|---|---|
| Servidor | Hetzner + Plesk (`intermedius.crececrm.com`) | aaPanel + nginx (`intermediusg.com`) | Medio (migración sin documentar) |
| API | `intermedius.crececrm.com/api/v1` | `api.intermediusg.com/api/v1` | Medio |
| Frontend Vue | No documentado | `admin.intermediusg.com` | Bajo |
| CI/CD backend | GitHub Actions → SSH | Confirmado | Bajo |
| CI/CD frontend | No documentado | GitHub Actions creado, no probado | Bajo |
| DB | MariaDB 10.3 localhost | Confirmado | Bajo (versión antigua) |
| Backups | No documentado | No verificado | 🔴 Crítico |
| Staging | No documentado | No existe | 🔴 Crítico |
| CDN | No documentado | No existe | Medio |

---

## 7. ROADMAP CORREGIDO — 2026

### SPRINT 0: Hardening crítico (1 semana) — JUNIO
- [ ] Rotar `DB_PASSWORD` expuesto y limpiar historial Git (`git filter-branch` o BFG)
- [ ] Cambiar password `admin@test.com` en producción
- [ ] Rate limiting en login (`throttle:5,1`)
- [ ] Verificar `APP_DEBUG=false` y `APP_ENV=production`
- [ ] Headers de seguridad (HSTS, CSP, X-Frame-Options)
- [ ] CORS: incluir dominio legacy durante transición

### SPRINT 1: Fix API-Frontend (2 semanas) — JUNIO
- [ ] **Decisión arquitectónica:** Backend acepta `tipo_operacion_id` (int) con lookup interno a `tipo_codigo`, O el frontend envía `tipo_codigo` directamente.
  - **Recomendación CTO:** Backend debe aceptar ambos (backward compatibility) o unificar en `tipo_codigo` y actualizar especificación.
- [ ] **Reescribir `OperacionFormView.vue`** con stepper de 5 pasos:
  - Paso 1: Tipo de operación (cards grandes: Venta USD, Compra USD, Cambio, Gasto)
  - Paso 2: Datos básicos (cliente autocomplete, referencia, descripción, fecha)
  - Paso 3: Monto y tasa (con validación favorable a la casa en tiempo real)
  - Paso 4: Movimientos contables (selector de cuenta + monto + tasa_a_usd)
  - Paso 5: Resumen con preview de comisiones y ganancia estimada
- [ ] Enviar payload completo: `tipo_codigo`, `fecha`, `operador_id`, `movimientos[]`
- [ ] Implementar vista de detalle con tabla de movimientos y comisiones
- [ ] Dashboard: resumen del día (operaciones hoy, USD movidos, ganancia bruta/neta, alertas)

### SPRINT 2: Catálogos y Configuración (2 semanas) — JUNIO/JULIO
- [ ] Titulares, Bancos, Monedas, Categorías de gasto (CRUD completo)
- [ ] Cuentas: crear/editar/activar-desactivar
- [ ] Clientes: editar y ver detalle con historial
- [ ] Configuración de comisiones (por cuenta, operador, método de pago)
- [ ] Historial de tasas por par de monedas

### SPRINT 3: Seguridad y Compliance (2 semanas) — JULIO
- [ ] Implementar 2FA TOTP
- [ ] Mover token de localStorage a httpOnly cookie (o implementar refresh token)
- [ ] Cifrar campos sensibles de clientes
- [ ] Logging de intentos fallidos de login
- [ ] Política de privacidad visible
- [ ] Alertas por email: login nuevo IP, operación > umbral, tasa no publicada

### SPRINT 4: Funcionalidades backend pendientes (3 semanas) — JULIO/AGOSTO
- [ ] Fase 3: Integración BCV (`dolarapi.com`) y Binance P2P
- [ ] Fase 4-A: Módulo de gastos completo
- [ ] Fase 4-C: FIFO — diseñar e implementar
- [ ] Endpoints de gestión de usuarios

### SPRINT 5: Importador Excel y Testing (2 semanas) — AGOSTO
- [ ] Analizar y mapear Excel histórico del cliente
- [ ] Importar con validación de cuadre contable
- [ ] Tests PHPUnit para `RegistroOperacionService`
- [ ] Tests E2E para Vue (Playwright)
- [ ] Sentry para monitoreo de errores

### SPRINT 6: Switchover (2 semanas) — AGOSTO/SEPTIEMBRE
- [ ] Capacitación a operadores
- [ ] Paralelo Excel + sistema (2 semanas)
- [ ] Reconciliación de reportes
- [ ] Switchover oficial

### SPRINT 7: Optimización y Escalabilidad (4 semanas) — SEPT-OCT
- [ ] Redis para cache de tasas y sesiones
- [ ] Optimizar queries N+1
- [ ] Pentest externo
- [ ] Compilar Flutter a APK (código ya existe, solo build)

---

## 8. LO QUE SÍ SE PUEDE HACER AHORA

✅ Iniciar sesión y navegar todas las pantallas
✅ Publicar tasas del día (admin)
✅ Ver lista de operaciones con estatus y ganancia
✅ Verificar operaciones manualmente (admin/contador)
✅ Crear clientes nuevos
✅ Ver lista de cuentas
✅ Consultar reportes de comisiones (sin exportar archivo)
✅ Demo a stakeholders

## 9. LO QUE NO SE PUEDE HACER TODAVÍA

❌ **Crear operaciones reales desde el frontend** (formulario roto, payload incompatible)
❌ Ver movimientos contables en detalle de operación
❌ Editar comisiones post-facto
❌ Eliminar operaciones desde frontend
❌ Operar sin supervisión admin (sin rate limiting ni 2FA)
❌ Importar historial de Excel (Fase 7 pendiente)
❌ Calcular FIFO (Fase 4-C pendiente)
❌ Ver tasas BCV/Binance en dashboard (Fase 3 pendiente)
❌ Cumplir normativa UIF/SUDEBAN (sin KYC)
❌ Descargar reportes Excel/PDF (stub, no genera archivo)
❌ Gestionar usuarios, titulares, bancos, monedas, categorías de gasto desde UI

---

## 10. RECOMENDACIONES INMEDIATAS DEL CTO (Priorizadas)

| Prioridad | Acción | Tiempo | Responsable |
|---|---|---|---|
| **P0** | Rotar credenciales expuestas (DB + admin) y limpiar Git | 2 horas | DevOps |
| **P0** | Verificar `APP_DEBUG=false` en producción | 15 min | Backend |
| **P0** | Agregar rate limiting a login | 2 horas | Backend |
| **P1** | Fix payload `OperacionFormView.vue` (`tipo_codigo`, `fecha`, `operador_id`, `movimientos[]`) | 1 día | Frontend |
| **P1** | Reescribir formulario con stepper de 5 pasos según especificación | 1 semana | Frontend |
| **P1** | Agregar tabla de movimientos y comisiones al detalle | 2 días | Frontend |
| **P2** | Implementar 2FA | 1 semana | Backend |
| **P2** | Mover token de localStorage a httpOnly cookie | 2 días | Frontend + Backend |
| **P2** | Dashboard con resumen del día y alertas | 3 días | Frontend |
| **P3** | Diseñar e implementar FIFO | 2 semanas | Backend Senior |
| **P3** | Importador Excel | 2 semanas | Backend |
| **P3** | Tests automatizados | 2 semanas | QA + Backend |

---

## 11. EQUIPO RECOMENDADO

| Rol | Cantidad | Tiempo | Función |
|---|---|---|---|
| Backend Senior (PHP/Laravel) | 1 | Full-time | FIFO, importador, arquitectura |
| Backend Mid (PHP/Laravel) | 1 | Full-time | Integraciones, reportes, tests |
| Frontend Senior (Vue 3) | 1 | Full-time | Fix formularios, dashboard, catálogos |
| QA / Automation Engineer | 1 | Medio | Tests PHPUnit, E2E, pentest interno |
| DevOps / SysAdmin | 1 | Medio | Infraestructura, backups, CI/CD, seguridad |
| Compliance / Legal advisor | 1 | Consultor | KYC, AML, políticas de privacidad, UIF |

---

## 12. RESUMEN EJECUTIVO

**El proyecto está en transición.** El backend Laravel es sólido y bien arquitecturado (~65% funcionalidades críticas). El frontend Vue 3 fue migrado desde Flutter y desplegado, pero **tiene un fallo crítico que impide crear operaciones** (payload incompatible con backend). Además, solo cubre ~50% de la especificación funcional documentada.

**Los 3 bloqueantes para producción son:**
1. 🔴 **Formulario de operaciones roto** — ninguna operación puede registrarse desde Vue.
2. 🔴 **Credenciales expuestas** — password de DB y admin en documentación del repo.
3. 🔴 **Sin KYC/AML** — riesgo regulatorio para operar como casa de cambio en Venezuela.

**Próximo paso recomendado:** SPRINT 0 (hardening) + fix del formulario de operaciones.

---

*Informe v3.0 preparado por el área de Tecnología de Intermedius Group.*
*Basado en revisión de código fuente Vue 3 (`/frontend/src/`), backend Laravel (`/api/`), documentación de contexto, y estado de deploy actual.*
*Próxima revisión: Junio 2026.*
