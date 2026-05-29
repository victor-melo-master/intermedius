# AUDITORÍA TÉCNICA — INTERMEDIUS GROUP
## Informe del CTO de Tecnología
**Fecha:** Mayo 2026 | **Clasificación:** Confidencial — Uso interno

---

## 1. RESUMEN EJECUTIVO

Intermedius Group es una plataforma ERP para casa de cambio que opera en Venezuela. El sistema gestiona operaciones de compra/venta de divisas, ledger contable, comisiones, clientes, cuentas bancarias y reportes.

### Veredicto general
| Rubro | Estado | Puntuación |
|---|---|---|
| Arquitectura Backend | **Aceptable con reservas** | 6.5/10 |
| Seguridad | **Crítico — acciones urgentes requeridas** | 4.5/10 |
| Frontend | **Funcional pero mínimo** | 6/10 |
| Calidad de Código | **Buena en backend, irregular en frontend** | 6.5/10 |
| Alineación API-Frontend | **Parcial** | 5/10 |
| Infraestructura/Deploy | **Operativo con deuda técnica** | 5.5/10 |
| Cumplimiento Normativo | **No cumple** | 3/10 |
| Escalabilidad | **Limitada** | 5/10 |

> **Recomendación CTO:** El sistema está **funcional para producción inmediata** pero con **riesgos de seguridad críticos** que deben mitigarse antes de manejar volúmenes reales de dinero. Se requiere un sprint de hardening de 2-3 semanas.

---

## 2. AUDITORÍA DEL BACKEND (Laravel 11)

### 2.1 Arquitectura y Diseño

#### ✅ Fortalezas
- **Arquitectura de capas correcta:** Separación clara entre Controllers, Services, Models, Requests, Policies y Resources.
- **Patrón Repository/Service:** `RegistroOperacionService` encapsula la lógica de negocio compleja con transacciones DB.
- **Uso de FormRequest:** Validación desacoplada en clases dedicadas (`StoreOperacionRequest`, `LoginRequest`, etc.).
- **Eloquent Relations bien definidas:** Modelos con relaciones typed (`BelongsTo`, `HasMany`).
- **Soft Deletes:** Implementado en modelos clave (`User`, `Operacion`).
- **Jobs asíncronos:** `RecalcularSaldoCuentaJob`, `ProcesarFifoOperacionJob` para operaciones pesadas.
- **Spatie Permissions:** Roles y permisos bien estructurados (`super_admin`, `admin`, `operador`, `contador`, `lectura`).
- **Activity Log:** Bitácora de auditoría implementada (`spatie/laravel-activitylog`).

#### ⚠️ Debilidades
- **Inconsistencia de namespaces:** Algunos controllers usan `App\Http\Controllers\Api\V1\` pero otros están en `App\Http\Controllers\` directo (sin namespace versionado). Esto dificulta el versioning de API.
- **Closures en rutas:** La ruta de bitácora (`admin/bitacora`) usa una closure inline en `api.php` en lugar de delegar a un controller. Esto viola separación de responsabilidades.
- **Falta de DTOs:** Los payloads viajan como arrays crudos; no hay Data Transfer Objects tipados.
- **No hay Event Sourcing ni CQRS:** Aunque el dominio es complejo (ledger contable), no se usa ningún patrón de eventos para trazabilidad inmutable.
- **Falta de API Versioning explícito:** El prefix `v1` existe pero no hay mecanismo de deprecación ni versionado de schemas.

### 2.2 Lógica de Negocio — Registro de Operaciones

El `RegistroOperacionService` es el núcleo del sistema. Análisis detallado:

#### ✅ Correcto
- **Invariante de partida doble validada:** La suma de `monto * tasa_a_usd` debe ser ≈ 0 (con tolerancia de $0.01). Esto es contablemente correcto.
- **Cálculo de ganancia congelado:** Las ganancias se snapshot al momento de la operación; no se recalculan posteriormente. Esto evita que cambios de tasa histórica afecten operaciones pasadas.
- **Validación de tasa efectiva:** El operador no puede aplicar una tasa desfavorable a la casa (venta < sugerida, compra > sugerida).
- **Rollback automático:** Todo el registro ocurre dentro de `DB::transaction`.

#### ⚠️ Riesgos de Lógica de Negocio
- **TODO sin implementar — Cambio multimoneda:** `case 'cambio':` retorna `['usd' => 0.0, 'ves' => 0.0]` (línea 341-345). Las operaciones de cambio no computan ganancia. Esto es un **bug funcional** si el negocio opera cambios EUR→VES, EUR→USD, etc.
- **División por cero implícita:** En `calcularGananciaBruta`, si `tasa_aplicada` o `tasa_mercado_snapshot` son 0, el cálculo falla silenciosamente. Aunque las validaciones previas deberían evitarlo, no hay guard explícito.
- **Falta de idempotencia en verificación:** `verificar()` no tiene protección contra doble-submit (race condition si dos admins hacen PATCH simultáneo).

### 2.3 Validación y Autorización

#### ✅ Correcto
- **Policies implementadas:** `OperacionPolicy`, `ClientePolicy`, etc. con `before()` para `super_admin`.
- **Request validation con reglas de negocio:** `StoreOperacionRequest` valida que `operador_id` sea el usuario autenticado (salvo super_admin).
- **Validación de cuentas inactivas:** Prevención de operaciones sobre cuentas bloqueadas.

#### ⚠️ Problemas
- **Falta de rate limiting en login:** No hay throttling en `auth/login`. Vulnerable a ataques de fuerza bruta.
- **Sin validación de 2FA:** En una casa de cambio, la autenticación de un solo factor no es suficiente para operaciones financieras.
- **Sin validación de IP o dispositivo:** No hay detección de sesiones sospechosas.

---

## 3. AUDITORÍA DE SEGURIDAD (OWASP Top 10)

### 3.1 A01:2021 — Broken Access Control

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Falta de rate limiting** | 🔴 **Crítico** | `auth/login` sin `throttle`. Ataque de fuerza bruta posible. |
| **CORS mal configurado** | 🟡 **Alto** | `allowed_origins` hardcodeados; wildcard `*` no está presente pero tampoco hay validación dinámica de orígenes. |
| **Sin Content Security Policy** | 🟡 **Alto** | No hay headers CSP en el backend ni en el frontend. |
| **Rutas de gastos sin Policy** | 🟠 **Medio** | `GastoController::store` no tiene Policy visible en `api.php`; se delega al middleware de roles pero no está claro. |
| **Sin validación de scope/token** | 🟠 **Medio** | Sanctum tokens son de "plain" sin scopes definidos. Un token de lectura puede hacer escritura. |

### 3.2 A02:2021 — Cryptographic Failures

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Sin encriptación de datos sensibles en DB** | 🔴 **Crítico** | Datos de clientes (teléfono, email, documento) en texto plano. Bajo normativas venezolanas y GDPR/LGPD, esto es inaceptable. |
| **Sin HSTS** | 🟡 **Alto** | No hay header `Strict-Transport-Security` forzado. |
| **Token almacenado en localStorage** | 🟡 **Alto** | El frontend Vue usa `localStorage` para el token. Vulnerable a XSS (aunque la app no tiene XSS obvios, localStorage es menos seguro que httpOnly cookies). |
| **SSL con certificado inválido** | 🟠 **Medio** | `api.intermediusg.com` usaba certificado que no cubría el subdominio. Ya corregido pero indica proceso de deploy frágil. |

### 3.3 A03:2021 — Injection

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Consulta SQL en bitácora** | 🟠 **Medio** | La closure `admin/bitacora` usa `like '%' . $request->input('modelo') . '%'` sin sanitización visible. Laravel lo escapa automáticamente por query builder, pero el patrón es riesgoso. |
| **Sin prepared statements explícitos** | 🟢 **Bajo** | Eloquent usa prepared statements por defecto; no hay SQL raw visible. |

### 3.4 A04:2021 — Insecure Design

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Sin circuit breaker** | 🟡 **Alto** | Si el servicio de tasas externo (BCV/Binance) falla, no hay fallback definido más allá de "usar última tasa". |
| **Sin reglas de negocio de compliance** | 🔴 **Crítico** | No hay validación de montos máximos por operación, no hay alertas de operaciones sospechosas (AML), no hay KYC digital. Para una casa de cambio, esto es **riesgo regulatorio extremo**. |
| **Sin auditoría de quién cambió qué** | 🟡 **Alto** | Activity log registra cambios pero no hay diff de valores anteriores/posteriores. |

### 3.5 A05:2021 — Security Misconfiguration

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **APP_DEBUG en producción** | 🔴 **Crítico** | Verificar `.env` en producción. Si `APP_DEBUG=true`, stack traces expuestos = información sensible filtrada. |
| **APP_ENV=local en servidor** | 🔴 **Crítico** | Mismo riesgo que arriba. |
| **CORS credentials** | 🟠 **Medio** | `supports_credentials => true` con múltiples orígenes sin wildcard. Puede funcionar pero es configuración sensible. |

### 3.6 A06:2021 — Vulnerable and Outdated Components

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Dependencias sin audit** | 🟠 **Medio** | No hay `composer audit` ni `npm audit` en CI/CD. Se necesita integrar Snyk o Dependabot. |
| **MariaDB 10.3** | 🟢 **Bajo** | Es una versión soportada pero relativamente antigua (2018). Considerar upgrade. |

### 3.7 A07:2021 — Identification and Authentication Failures

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Sin rate limiting** | 🔴 **Crítico** | (Repetido) Login expuesto a fuerza bruta. |
| **Sin 2FA** | 🔴 **Crítico** | En un sistema financiero, 1FA es inaceptable. |
| **Sin revocación de tokens masiva** | 🟠 **Medio** | No hay endpoint para revocar todos los tokens de un usuario comprometido. |
| **Token expuesto en respuesta JSON** | 🟠 **Medio** | `login` retorna token en body. Mejor práctica: httpOnly cookie. |

### 3.8 A08:2021 — Software and Data Integrity Failures

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Sin firma de webhooks** | 🟢 **Bajo** | No hay webhooks externos, por ahora no aplica. |
| **Sin checksum de jobs** | 🟢 **Bajo** | Jobs en queue no tienen protección contra tampering. |

### 3.9 A09:2021 — Security Logging and Monitoring Failures

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Sin SIEM ni alertas** | 🟡 **Alto** | Activity log registra eventos pero no hay alertas automáticas (email/Slack/Telegram) para eventos críticos. |
| **Sin logging de intentos fallidos** | 🟠 **Medio** | No hay tracking de logins fallidos para detección de intrusiones. |

### 3.10 A10:2021 — Server-Side Request Forgery (SSRF)

| Hallazgo | Severidad | Detalle |
|---|---|---|
| **Tasas de mercado externas** | 🟠 **Medio** | Si se implementan llamadas a BCV/Binance, validar URLs para prevenir SSRF. |

---

## 4. AUDITORÍA DEL FRONTEND

### 4.1 Vue 3 (Nueva versión)

| Aspecto | Estado |
|---|---|
| Framework | Vue 3 + Vite + TailwindCSS + Pinia |
| Estado management | Pinia con stores modulares (auth, operaciones, tasas, clientes) |
| Routing | Vue Router con lazy loading y auth guards |
| HTTP Client | Axios con interceptores de token y 401 handler |
| Responsive | TailwindCSS con grid responsive |
| Build | Vite optimizado |

#### ✅ Fortalezas
- **Arquitectura limpia:** Separación API → Stores → Views → Components.
- **Auth guards funcionales:** Router intercepta rutas protegidas.
- **Lazy loading:** Views cargan bajo demanda.
- **Responsive nativo:** Tailwind con `md:`, `lg:` breakpoints.
- **Token interceptor:** Axios inyecta Bearer automáticamente.
- **Sin frameworks UI pesados:** Bundle ~144KB JS gzipped (excelente).

#### ⚠️ Debilidades Frontend Vue
- **Token en localStorage:** Vulnerable a XSS. Debería migrar a `httpOnly` cookie + `withCredentials: true`.
- **Sin CSRF protection:** Si se migra a cookies, necesita Laravel Sanctum CSRF cookie.
- **Sin input sanitization:** Los inputs de formularios no escapan HTML antes de mostrarse. Riesgo de XSS reflejado.
- **Sin Content Security Policy:** No hay meta tag CSP en `index.html`.
- **Error handling básico:** Solo muestra `err.message` o `err.response.data.message`. No hay retry automático ni circuit breaker.
- **Sin tests:** No hay unit tests, e2e tests ni integration tests.

### 4.2 Flutter (Versión anterior — deprecada)

El frontend Flutter fue construido pero no se desplegó. Contiene:
- `flutter_secure_storage` para token (mejor que localStorage de Vue).
- Dio con interceptores.
- GoRouter con auth guards.
- Screens completas.

**Recomendación:** Conservar como activo técnico para futura app móvil. No eliminar.

---

## 5. ALINEACIÓN API ↔ FRONTEND

### 5.1 Endpoints mapeados correctamente ✅
| Frontend | Endpoint API | Estado |
|---|---|---|
| Login | `POST /auth/login` | ✅ Mapeado |
| Logout | `POST /auth/logout` | ✅ Mapeado |
| Me | `GET /auth/me` | ✅ Mapeado |
| Dashboard tasas | `GET /configuracion/tasas-vigentes` | ✅ Mapeado |
| Operaciones list | `GET /operaciones` | ✅ Mapeado |
| Operación detail | `GET /operaciones/{id}` | ✅ Mapeado |
| Operación crear | `POST /operaciones` | ⚠️ **Desajuste** (ver nota) |
| Verificar operación | `PATCH /operaciones/{id}/verificar` | ✅ Mapeado |
| Tasas publicar | `POST /configuracion/tasas-diarias` | ✅ Mapeado |
| Clientes | `GET /clientes` | ✅ Mapeado |
| Cuentas | `GET /cuentas` | ✅ Mapeado |
| Reportes | `GET /reportes/comisiones-operadores` | ✅ Mapeado |

### 5.2 Desajustes críticos ⚠️

#### A. Creación de operación — Payload incompatible
- **Frontend envía:** `{ tipo_operacion_id, cliente_id, tasa_aplicada, referencia, descripcion }` (simplificado).
- **Backend espera:** `{ fecha, tipo_codigo, operador_id, movimientos: [{cuenta_id, monto, tasa_a_usd}], ... }` (ledger completo).

**Problema:** El frontend Vue no incluye los `movimientos` contables. La API requiere partida doble (mínimo 2 movimientos con cuentas, montos y tasas). El formulario actual del frontend es un stub que NO funcionará para crear operaciones reales.

**Impacto:** 🔴 **Crítico funcional** — El botón "Registrar operación" fallará con error 422 de validación.

#### B. Falta de endpoints consumidos en frontend
- `GET /tasas/actuales` — No se consume (BCV/Binance).
- `GET /dashboard/general` — No se consume en el dashboard Vue.
- `GET /gastos` — No hay vista de gastos en Vue.
- Comisiones por operación (`/operaciones/{id}/comisiones`) — No se muestran en detalle.

#### C. Frontend envía `tipo_operacion_id` (int) pero backend espera `tipo_codigo` (string)
El backend usa `TipoOperacion::where('codigo', $payload['tipo_codigo'])`. El frontend envía `tipo_operacion_id: Number(...)`. Esto es un **desajuste de schema**.

---

## 6. CUMPLIMIENTO NORMATIVO E ISO

### 6.1 ISO 27001 (Gestión de Seguridad de la Información)

| Control | Estado | Nota |
|---|---|---|
| A.9.2.1 — Registro y cancelación de usuarios | 🟡 Parcial | Soft delete implementado pero sin workflow de aprobación. |
| A.9.2.4 — Eliminación de accesos | 🟡 Parcial | Tokens se revocan en logout pero no hay expiración automática. |
| A.9.4.2 — Procedimientos de login seguro | 🔴 No cumple | Sin 2FA, sin rate limiting, sin captcha. |
| A.10.1.1 — Políticas de criptografía | 🔴 No cumple | Sin cifrado de datos sensibles en reposo. |
| A.12.3.1 — Copias de respaldo | 🔴 No cumple | No hay sistema de backups documentado en el código. |
| A.12.6.1 — Gestión de vulnerabilidades | 🔴 No cumple | Sin escaneo automatizado de dependencias. |
| A.16.1.1 — Responsabilidades y procedimientos | 🟡 Parcial | Activity log existe pero sin alertas. |

### 6.2 ISO 9001 (Calidad)

| Requisito | Estado | Nota |
|---|---|---|
| Documentación del proceso | 🟡 Parcial | Docblocks en services pero sin documentación de usuario. |
| Gestión de cambios | 🟡 Parcial | Git usado pero sin CHANGELOG ni semantic versioning. |
| Testing | 🔴 No cumple | Sin tests automatizados. |
| Monitoreo y medición | 🔴 No cumple | Sin métricas de rendimiento ni APM. |

### 6.3 Normativas Financieras / AML

| Requisito | Estado | Nota |
|---|---|---|
| KYC (Know Your Customer) | 🔴 No implementado | Clientes se crean sin validación de identidad. |
| AML / UIF (Venezuela) | 🔴 No implementado | Sin reportes de operaciones sospechosas. Sin umbrales de alerta. |
| Tratamiento de datos personales | 🔴 No implementado | Sin política de privacidad, sin consentimiento, sin cifrado. |
| Retención documental | 🟡 Parcial | Soft deletes + activity log pero sin política de retención. |

---

## 7. DEUDA TÉCNICA

### Deuda Crítica (arreglar en 2 semanas)
1. **Rate limiting en login** — 1 día de trabajo.
2. **Fix del payload de operaciones en frontend** — 3-5 días.
3. **APP_DEBUG=false en producción** — 1 hora.
4. **CORS dinámico seguro** — 1 día.
5. **Migrar token a httpOnly cookie** — 2 días.

### Deuda Alta (arreglar en 1 mes)
6. Implementar 2FA (TOTP) — 1 semana.
7. Cifrar datos sensibles de clientes en DB — 3 días.
8. Tests automatizados (PHPUnit + Vitest) — 2 semanas.
9. CI/CD con escaneo de vulnerabilidades — 3 días.
10. Validación de montos máximos y alertas AML — 1 semana.

### Deuda Media (arreglar en 3 meses)
11. Implementar KYC digital.
12. Dashboard de métricas de negocio.
13. Circuit breaker para servicios externos.
14. Migrar a API versioning formal (v1, v2).
15. Implementar DTOs tipados.

---

## 8. ROADMAP ESTRATÉGICO — 2026

### Fase 1: Hardening de Seguridad (Junio 2026) — 3 semanas
- [ ] Rate limiting en login y API crítica
- [ ] 2FA con TOTP para todos los usuarios
- [ ] APP_DEBUG=false + headers de seguridad (HSTS, CSP, X-Frame-Options)
- [ ] Cifrado de datos sensibles en DB (clientes, cuentas)
- [ ] Migrar token a httpOnly cookie + Sanctum SPA mode
- [ ] Fix payload operaciones frontend

### Fase 2: Compliance y AML (Julio 2026) — 4 semanas
- [ ] KYC digital (subida de documentos, validación OCR)
- [ ] Umbrales de alerta por operación (ej: >$10,000 = notificación)
- [ ] Reportes de operaciones sospechosas para UIF
- [ ] Consentimiento digital y política de privacidad
- [ ] Retención documental automatizada

### Fase 3: Escalabilidad y Observabilidad (Agosto 2026) — 3 semanas
- [ ] Tests automatizados (unit, integration, e2e)
- [ ] CI/CD con GitHub Actions + escaneo de vulnerabilidades
- [ ] APM (Application Performance Monitoring) — Sentry o New Relic
- [ ] Redis para cache de tasas y sesiones
- [ ] Circuit breaker para BCV/Binance APIs

### Fase 4: Funcionalidades Avanzadas (Sept-Oct 2026) — 6 semanas
- [ ] App móvil Flutter (usar el código ya construido)
- [ ] Integración BCV + Binance para tasas automáticas
- [ ] FIFO completo para inventario de divisas
- [ ] Dashboard avanzado con P&L en tiempo real
- [ ] APIs para terceros (webhooks)

### Fase 5: Auditoría y Certificación (Nov 2026) — 4 semanas
- [ ] Pentest externo
- [ ] Preparación ISO 27001
- [ ] Auditoría contable externa del ledger
- [ ] Documentación técnica completa

---

## 9. LO QUE SÍ SE PUEDE HACER AHORA

✅ Operar con volúmenes bajos (< 50 operaciones/día) con supervisión manual  
✅ Registrar operaciones con verificación de admin  
✅ Publicar tasas diarias  
✅ Gestionar clientes, cuentas y catálogos  
✅ Generar reportes de comisiones  
✅ Desplegar en producción con monitoreo manual  

## 10. LO QUE NO SE PUEDE HACER TODAVÍA

❌ Operar sin supervisión admin (sin validaciones AML automáticas)  
❌ Manejar volúmenes altos sin tests automatizados  
❌ Expansión a otros países sin compliance normativo  
❌ App móvil en producción (Flutter no desplegado)  
❌ KYC digital automatizado  
❌ Detección de fraude automatizado  

---

## 11. RECOMENDACIONES INMEDIATAS DEL CTO

1. **NO habilitar operaciones reales** hasta que se implemente rate limiting + 2FA.
2. **Asignar un QA engineer** para escribir tests de integración del ledger.
3. **Contratar pentest externo** antes de manejar dinero real de clientes.
4. **Documentar el modelo contable** (partida doble, FIFO, comisiones) para auditoría.
5. **Separar entornos:** staging y producción deben ser dominios distintos con configs separadas.
6. **Backup diario automatizado** de la base de datos (si no existe, es prioritario).

---

*Informe preparado por el área de Tecnología de Intermedius Group.*  
*Próxima revisión: Junio 2026.*
