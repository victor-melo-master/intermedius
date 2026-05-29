# AUDITORÍA TÉCNICA — INTERMEDIUS GROUP v2.0
## Informe corregido del CTO de Tecnología
**Fecha:** Mayo 2026 | **Clasificación:** Confidencial — Uso interno
**Alcance:** Backend Laravel 11, Frontend Vue 3 (migrado desde Flutter), Infraestructura actual, Contexto de negocio

---

## 1. CORRECCIONES AL INFORME ANTERIOR

| # | Error en v1 | Realidad verificada en contexto |
|---|---|---|
| 1 | Se dijo "Flutter deprecado, vacío, conservar como activo" | Flutter **SÍ fue implementado** en esta sesión (OperacionFormScreen, TasasScreen, ClientesScreen, etc. en `/app/lib/`). No se desplegó, pero tiene lógica de negocio completa. |
| 2 | Se asumió servidor aaPanel como infraestructura definitiva | El **contexto original** indica Hetzner VPS + Plesk (`intermedius.crececrm.com`). El dominio `intermediusg.com` con aaPanel es una **migración reciente no documentada** en el contexto. |
| 3 | No se reportó fuga de credenciales en documentación | `intermedius-contexto-proyecto.md` línea 43 expone `DB_PASSWORD: MSlx0^k64vop!2025` en texto plano. Fuga crítica. |
| 4 | CI/CD marcado como "no implementado" | El contexto confirma GitHub Actions existente en `.github/workflows/deploy.yml` para backend (SSH + composer + migrate + cache). |
| 5 | Se omitió el desajuste `tipo_operacion_id` vs `tipo_codigo` en la especificación | El documento de funcionalidades (Pantalla 3.3) especifica `tipo_operacion_id: int`, pero el backend implementa `tipo_codigo: string`. La especificación y el backend están **desalineados desde el diseño**. |
| 6 | No se analizó el gap del Dashboard según especificación | El contexto funcionalidades exige dashboard con: tasas BCV/Binance, resumen del día (operaciones, USD movidos, ganancia bruta/neta), alertas. El Vue actual solo muestra tasas vigentes. |
| 7 | No se mencionó MariaDB collation workaround | El contexto documenta `DB_COLLATION=utf8mb4_general_ci` y `Schema::defaultStringLength(191)` como workaround obligatorio para MariaDB 10.3. |

---

## 2. ESTADO REAL DEL PROYECTO (vs. Plan de Fases)

El contexto define 8 fases. Estado real:

| Fase | Descripción | Estado real | Riesgo |
|---|---|---|---|
| 1 | Maestros y autenticación | ✅ Completa | Bajo |
| 2 | Ledger de operaciones y movimientos | ✅ Completa | Bajo |
| 2.5 | Configuración (tasas, comisiones, bitácora, reportes) | ✅ Completa | Bajo |
| 3 | Integración BCV/Binance (tasas de mercado) | ❌ Pendiente | Medio |
| 4-A | Módulo de gastos | ⚠️ Parcial (endpoint existe, sin UI) | Medio |
| 4-C | FIFO — costeo por lotes | ❌ Pendiente | Alto (documentado como "la más compleja") |
| 5 | Dashboards avanzados | ⚠️ Parcial (general existe, no consume datos reales de volumen) | Medio |
| 6 | Pulido, auditoría, hardening | 🔄 En curso (esta auditoría) | Alto |
| 7 | Importador desde Excel histórico | ❌ Pendiente | Alto (bloquea switchover) |
| 8 | Switchover (cliente deja Excel) | ❌ Bloqueado por Fase 7 | Crítico |

> **Conclusión:** El backend está **~65% completo** en funcionalidades críticas. La Fase 7 (importador Excel) es **bloqueante** para la operación real del negocio (~600 clientes y años de historial en Excel).

---

## 3. AUDITORÍA DEL BACKEND (Laravel 11)

### 3.1 Arquitectura — Revisión contra Contexto

El contexto describe una arquitectura de capas que **sí está implementada**:
- `Controllers/Api/V1/` ✅
- `Services/Operaciones/`, `Services/Configuracion/` ✅
- `Models/` con relaciones typed ✅
- `Jobs/` asíncronos ✅
- `Policies/` ✅
- `Requests/` ✅

#### ⚠️ Desviaciones detectadas

**A. Inconsistencia `tipo_codigo` vs `tipo_operacion_id`**
- **Backend:** `RegistroOperacionService::registrar()` usa `TipoOperacion::where('codigo', $payload['tipo_codigo'])`.
- **Especificación frontend:** `intermedius-funcionalidades-pantallas.md` línea 294 manda `tipo_operacion_id: 1`.
- **Impacto:** Cualquier frontend construido según la especificación oficial fallará con 422. El Vue que construimos usa `tipo_operacion_id` por error. **Ningún frontend actual funciona para crear operaciones.**

**B. Especificación de movimientos incluye `moneda_id` redundante**
- **Especificación:** `movimientos: [{ cuenta_id, monto, moneda_id }]`
- **Backend:** `StoreOperacionRequest` solo valida `cuenta_id`, `monto`, `tasa_a_usd`. El `moneda_id` se infiere desde la cuenta en `RegistroOperacionService` línea 81.
- **Impacto:** Bajo. El backend ignora el campo, pero genera confusión en la integración.

**C. `admin/bitacora` como closure inline**
- Confirmado en `api/routes/api.php` líneas 92-110. El contexto no documenta esto pero está presente. Debilidad de arquitectura.

### 3.2 Lógica de Negocio — Ledger y FIFO

El contexto documenta claramente que **FIFO no está implementado** (Fase 4-C pendiente). El informe v1 no mencionó esto como riesgo para el roadmap.

| Riesgo | Severidad | Justificación |
|---|---|---|
| FIFO pendiente | 🔴 **Alto** | El contexto dice "FIFO es la parte más compleja. Requiere diseño cuidadoso antes de implementar." Sin FIFO no hay costeo real de inventario de divisas. |
| `case 'cambio':` retorna `$0` ganancia | 🟡 **Medio** | Confirmado en código. El contexto dice "Cambio multimoneda" como tipo principal pero el cálculo está en TODO. |
| `tasa_mercado_snapshot` no se usa para ganancia | 🟢 **Bajo** | El contexto confirma línea 266: "BCV/Binance son SOLO referenciales". Correcto por diseño. |

### 3.3 Seguridad — Revisión OWASP con Contexto

#### Hallazgos CRÍTICOS no reportados en v1

**1. Credenciales de base de datos expuestas en documentación**
- **Ubicación:** `intermedius-contexto-proyecto.md` línea 43.
- **Dato expuesto:** `DB_PASSWORD: MSlx0^k64vop!2025`
- **Impacto:** Cualquier persona con acceso al repo tiene la contraseña de producción.
- **Acción inmediata:** Rotar password en producción. Eliminar del historial de Git (`git filter-branch` o `BFG Repo-Cleaner`).

**2. Credenciales de admin expuestas en documentación**
- **Ubicación:** `intermedius-contexto-proyecto.md` línea 47.
- **Dato expuesto:** `admin@test.com` / `password123`
- **Impacto:** Password default documentada. Si no se cambió en producción, servidor comprometido.

**3. Rate limiting**
- El contexto no menciona rate limiting en ningún lado. El informe v1 lo detectó correctamente como crítico.

**4. `APP_ENV` y `APP_DEBUG`**
- El contexto documenta `APP_ENV=production` y `APP_DEBUG=false` como config esperada, pero no hay verificación de que esto sea real en el servidor.

#### Hallazgos de CORS — Corregido vs. Contexto

El contexto original lista la URL de API como `https://intermedius.crececrm.com/api/v1`. Sin embargo, en esta sesión se desplegó en `https://api.intermediusg.com`. El CORS debe incluir **ambos dominios** durante la transición:

```php
'allowed_origins' => [
    'https://intermedius.crececrm.com',  // legacy
    'https://admin.intermediusg.com',      // nuevo Vue
    'https://app.intermediusg.com',        // posible futuro
],
```

> Nota: El contexto no documenta CORS en absoluto. Fue agregado durante esta sesión.

---

## 4. AUDITORÍA DEL FRONTEND

### 4.1 Estado real de los frontends

| Frontend | Ubicación | Estado | Deploy |
|---|---|---|---|
| **Flutter** | `/app/lib/` | ✅ Pantallas completas (OperacionFormScreen, TasasScreen, ClientesScreen, CuentasScreen, ReportesScreen, OperacionesScreen, etc.) | ❌ No desplegado |
| **Vue 3** | `/frontend/` | ✅ Estructura completa (Login, Dashboard, Operaciones list/form/detail, Tasas, Clientes, Cuentas, Reportes, AppShell) | ✅ Desplegado en `admin.intermediusg.com` |

#### Corrección al v1:
El Flutter NO está vacío. Fue completamente implementado en esta sesión con:
- `lib/core/auth/auth_provider.dart` (Riverpod AuthNotifier)
- `lib/core/network/dio_client.dart` (Dio + interceptor)
- `lib/core/storage/secure_storage.dart` (FlutterSecureStorage — **mejor que localStorage de Vue**)
- `lib/features/operaciones/operacion_form_screen.dart` (285 líneas, form completo)
- `lib/features/tasas/tasas_screen.dart` (277 líneas)
- `lib/features/clientes/clientes_screen.dart` (219 líneas)
- `lib/features/cuentas/cuentas_screen.dart`
- `lib/features/reportes/reportes_screen.dart` (160 líneas)
- `lib/app_router.dart` (GoRouter)

**Recomendación corregida:** No eliminar Flutter. Es un **activo funcional completo** que puede desplegarse como PWA o compilarse a APK en el futuro. El Vue fue creado porque el usuario pidió "algo más ligero y responsive", no porque Flutter estuviera roto.

### 4.2 Vue 3 — Gap funcional contra especificación

El documento `intermedius-funcionalidades-pantallas.md` define requisitos detallados que el Vue actual **NO cumple**:

| Requisito de especificación | Estado en Vue actual | Impacto |
|---|---|---|
| Dashboard: tasas BCV/Binance de referencia | ❌ No implementado | El contexto dice Fase 3 pendiente, pero la UI debería preparar el slot. |
| Dashboard: resumen del día (operaciones hoy, USD movidos, ganancia bruta/neta) | ❌ No implementado | Dashboard solo muestra tasas vigentes y fecha. |
| Dashboard: alertas (operaciones sin_verificar, sin_tasa_referencia) | ❌ No implementado | No hay badges ni alertas visuales. |
| Detalle de operación: sección "Movimientos" con tabla | ⚠️ Parcial | Muestra datos básicos pero no tabla de movimientos ni comisiones. |
| Detalle de operación: botón "Editar comisión" | ❌ No implementado | Solo admin puede editar comisiones según contexto. |
| Registrar operación: stepper de 5 pasos | ❌ No implementado | Formulario simplificado de 1 paso. No incluye movimientos contables. |
| Registrar operación: selector de cuentas con saldo | ❌ No implementado | No hay integración con cuentas en el form. |
| Registrar operación: preview de comisiones antes de confirmar | ❌ No implementado | |
| Catálogos: Titulares, Bancos, Monedas, Categorías de gasto | ❌ No implementado | Solo Clientes y Cuentas. |
| Gestión de usuarios | ❌ No implementado | No hay vista de usuarios. |
| Formato de monedas (VES con separadores, USD con "$", USDT con "₮") | ⚠️ Parcial | USD formateado, VES/USDT no diferenciados visualmente. |

**Conclusión:** El Vue actual es un **MVP funcional** que cubre ~40% de los requisitos documentados en `intermedius-funcionalidades-pantallas.md`. Es operable para demo pero **no para producción según especificación oficial**.

### 4.3 Desajuste crítico de API-Frontend — Confirmado

El informe v1 detectó correctamente que el frontend Vue envía un payload incompatible con el backend. Aquí la matriz de verificación:

| Campo | Vue envía | Backend espera | Coincide |
|---|---|---|---|
| `tipo_codigo` / `tipo_operacion_id` | `tipo_operacion_id: Number` (int) | `tipo_codigo: string` (ej: "venta_usd") | ❌ **NO** |
| `movimientos` | No envía | Requerido (`array` min 2 para venta/compra) | ❌ **NO** |
| `fecha` | No envía | Requerida (`date`) | ❌ **NO** |
| `operador_id` | No envía | Requerido (`integer`) | ❌ **NO** |
| `tasa_aplicada` | Sí, `number` | `nullable numeric` | ✅ Sí |
| `cliente_id` | Sí, `number` | `nullable integer` | ✅ Sí |
| `referencia` | Sí, `string` | `nullable string` | ✅ Sí |
| `descripcion` | Sí, `string` | `nullable string` | ✅ Sí |

**Veredicto:** El formulario de operaciones del Vue es un **stub que no funciona**. Al hacer submit, el backend responderá 422 con múltiples errores de validación.

---

## 5. CUMPLIMIENTO NORMATIVO E ISO — Revisado

### 5.1 ISO 27001 — Controles verificados

| Control ISO 27001:2022 | Implementado | Evidencia en código/contexto |
|---|---|---|
| A.5.7 — Threat intelligence | ❌ No | Sin integración con feeds de amenazas. |
| A.5.9 — Inventory of information assets | ⚠️ Parcial | Activity log existe pero sin inventario formal de activos. |
| A.5.15 — Access control | 🟡 Parcial | Spatie roles implementados pero sin MFA. |
| A.5.17 — Authentication information | 🔴 No | Credenciales en documento markdown. |
| A.5.23 — Cloud services | 🟡 Parcial | Deploy en VPS propio (Hetzner), sin controles cloud específicos. |
| A.5.29 — Information security during disruption | ❌ No | Sin plan de continuidad documentado. |
| A.5.30 — ICT readiness for business continuity | ❌ No | Sin redundancia ni failover. |
| A.5.37 — Documented operating procedures | ⚠️ Parcial | Docblocks en services pero sin runbooks. |
| A.6.4 — Disciplinary process | ❌ No | Sin política de sanciones documentada. |
| A.6.5 — Responsibilities after termination | 🟡 Parcial | Soft delete de usuarios existe pero sin procedimiento formal. |
| A.6.7 — Remote working | 🟡 Parcial | API accesible remotamente pero sin política de VPN. |
| A.6.8 — Information security event reporting | 🟡 Parcial | Activity log registra eventos pero sin canal de reporte. |
| A.7.7 — Clear desk and clear screen | 🔴 No | Credenciales en documento impreso/digital. |
| A.8.2 — Privileged access rights | 🟡 Parcial | `super_admin` existe pero sin separación de deberes (SoD). |
| A.8.5 — Secure authentication | 🔴 No | Sin 2FA, sin rate limiting. |
| A.8.7 — Protection against malware | ❌ No | Sin antivirus ni EDR en servidor. |
| A.8.8 — Management of technical vulnerabilities | 🔴 No | Sin escaneo automatizado. |
| A.8.9 — Configuration management | 🟡 Parcial | `.env` documentado pero sin gestión de cambios formal. |
| A.8.10 — Deletion of information | 🟡 Parcial | Soft deletes implementados. |
| A.8.11 — Data masking | 🔴 No | Datos sensibles de clientes en texto plano. |
| A.8.12 — Data leakage prevention | 🔴 No | Sin DLP. Documento con credenciales expuesto. |
| A.8.16 — Monitoring activities | 🟡 Parcial | Activity log existe pero sin SIEM. |
| A.8.23 — Web filtering | ❌ No | Sin proxy ni filtro web. |
| A.8.24 — Use of cryptography | 🔴 No | Sin cifrado de datos sensibles en reposo. |
| A.8.26 — Application security requirements | 🔴 No | Sin SDL/SDLC formal, sin tests de seguridad. |
| A.8.28 — Secure coding | 🟡 Parcial | Código limpio pero sin revisión de seguridad formal. |
| A.8.31 — Separation of development, test and production | 🔴 No | No hay entorno de staging documentado. |
| A.8.32 — Change management | 🟡 Parcial | Git + CI/CD existe pero sin CAB. |

> **Resultado ISO 27001:** El sistema cumple aproximadamente **25-30%** de los controles esenciales. No está listo para certificación.

### 5.2 Normativa Venezolana / AML / UIF

| Requisito | Estado | Nota |
|---|---|---|
| Registro de operaciones con trazabilidad | ✅ Parcial | Activity log + ledger. Falta firma digital. |
| Reportes de operaciones sospechosas a UIF | ❌ No | El contexto no menciona UIF ni SUDEBAN. |
| Límites de operación por cliente/monto | ❌ No | Sin umbrales configurables. |
| KYC digital (foto de cédula, dirección, PEP) | ❌ No | Clientes solo tienen nombre, alias, teléfono. |
| Retención de registros (5-10 años) | 🟡 Parcial | Soft deletes + DB backups (si existen). |
| Política de privacidad visible al usuario | ❌ No | No hay en frontend ni backend. |

> **Riesgo regulatorio:** Operar una casa de cambio en Venezuela sin KYC ni reportes UIF expone al cliente a sanciones de la SUDEBAN. Esto es **bloqueante legal**.

---

## 6. INFRAESTRUCTURA Y DEPLOY

### 6.1 Configuración documentada vs. Real

| Aspecto | Documentado en contexto | Estado en esta sesión | Riesgo |
|---|---|---|---|
| Servidor | Hetzner VPS, Plesk | aaPanel + nginx en posible servidor diferente | Medio (transición sin documentación) |
| Dominio API | `intermedius.crececrm.com` | `api.intermediusg.com` | Medio (DNS/certs duplicados) |
| Dominio frontend | No documentado | `admin.intermediusg.com` | Bajo |
| SSL | Let's Encrypt vía Plesk | Let's Encrypt vía aaPanel | Bajo |
| DB | MariaDB 10.3.31 localhost | MariaDB 10.3 (confirmado por contexto) | Bajo |
| CI/CD backend | GitHub Actions → SSH | Confirmado por contexto | Bajo |
| CI/CD frontend | No documentado | GitHub Actions creado en esta sesión | Bajo (nuevo, no probado) |
| Backups | No documentado | No verificado | 🔴 **Crítico** |

### 6.2 Deuda técnica de infraestructura

1. **Sin entorno de staging:** El contexto no documenta un servidor staging. Todo se prueba en producción.
2. **Migración de dominio sin plan:** El cambio de `crececrm.com` a `intermediusg.com` no está documentado. ¿Qué pasa con el SSL del dominio anterior?
3. **MariaDB 10.3 workaround:** `utf8mb4_general_ci` + `Schema::defaultStringLength(191)` son deuda técnica de versión antigua. Upgrade a MariaDB 10.6+ eliminaría esto.
4. **Sin CDN:** Assets estáticos servidos desde VPS directo. Latencia alta para usuarios en Venezuela.

---

## 7. ROADMAP CORREGIDO — 2026

### SPRINT 0: Hardening crítico (1 semana) — JUNIO
- [ ] Rotar `DB_PASSWORD` expuesto en documentación + limpiar historial Git
- [ ] Cambiar password de `admin@test.com` en producción
- [ ] Rate limiting en login (`throttle:5,1`)
- [ ] Verificar `APP_DEBUG=false` y `APP_ENV=production` en servidor real
- [ ] Agregar headers de seguridad (HSTS, CSP, X-Frame-Options)
- [ ] Fix CORS para incluir dominio legacy `intermedius.crececrm.com` durante transición

### SPRINT 1: Fix API-Frontend (2 semanas) — JUNIO
- [ ] **Decisión arquitectónica:** ¿Backend acepta `tipo_operacion_id` o frontend envía `tipo_codigo`?
  - Recomendación: Backend debería aceptar ambos (backward compatibility) o unificar en `tipo_codigo` y actualizar especificación.
- [ ] **Reescribir `OperacionFormView.vue`** para incluir stepper de 5 pasos según especificación oficial
  - Paso 1: Tipo de operación (cards grandes)
  - Paso 2: Datos básicos (cliente, referencia, descripción)
  - Paso 3: Monto y tasa (con validación favorable a la casa)
  - Paso 4: Movimientos contables (selector de cuenta + monto)
  - Paso 5: Resumen con preview de comisiones y ganancia estimada
- [ ] Enviar `movimientos`, `fecha`, `operador_id`, `tipo_codigo` en el payload
- [ ] Implementar vista de detalle con tabla de movimientos y comisiones
- [ ] Dashboard: agregar resumen del día (operaciones hoy, USD movidos, ganancia)

### SPRINT 2: Seguridad y Compliance (2 semanas) — JUNIO/JULIO
- [ ] Implementar 2FA (TOTP) con `pragmarx/google2fa-laravel`
- [ ] Cifrar campos sensibles de clientes con `laravel-encryption` o `spatie/laravel-data`
- [ ] Implementar logging de intentos fallidos de login
- [ ] Agregar alertas por email para: login desde IP nueva, operación > umbral, tasa no publicada
- [ ] Política de privacidad visible en login

### SPRINT 3: Funcionalidades pendientes backend (3 semanas) — JULIO
- [ ] Fase 3: Integración BCV (`dolarapi.com`) y Binance P2P — job cada 15 min
- [ ] Fase 4-A: Módulo de gastos completo (vista en frontend + endpoints)
- [ ] Fase 4-C: FIFO — diseñar antes de implementar (complejidad alta)
- [ ] Endpoints de gestión de usuarios (solo super_admin)

### SPRINT 4: Importador Excel (2 semanas) — JULIO/AGOSTO
- [ ] Analizar estructura de Excel histórico del cliente
- [ ] Mapear hojas (BOLIVARES, DOLARES, CAMBIOS, GASTOS, COMISIONES) a operaciones/movimientos
- [ ] Validar cuadre contable de datos importados
- [ ] Probar con muestra de 100 operaciones antes de importar todo

### SPRINT 5: Testing y Observabilidad (2 semanas) — AGOSTO
- [ ] Tests PHPUnit para `RegistroOperacionService` (casos: venta_usd, compra_usd, cambio, gasto, ajuste)
- [ ] Tests de integración para flujo completo: login → crear operación → verificar → reporte
- [ ] Tests E2E para Vue (Playwright o Cypress)
- [ ] Sentry para monitoreo de errores en producción
- [ ] phpMyAdmin o similar protegido por VPN para acceso a DB

### SPRINT 6: Switchover y Capacitación (2 semanas) — AGOSTO/SEPTIEMBRE
- [ ] Capacitación a operadores (Johan y equipo)
- [ ] Paralelo: operar 2 semanas con Excel + sistema simultáneo
- [ ] Validar reportes contra Excel (reconciliación)
- [ ] Switchover oficial: dejar Excel

### SPRINT 7: App Móvil y Escalabilidad (4 semanas) — SEPT-OCT
- [ ] Compilar Flutter a APK/PWA (el código ya existe)
- [ ] Redis para cache de tasas y sesiones
- [ ] Optimizar queries N+1 en operaciones con muchos movimientos
- [ ] Pentest externo

---

## 8. LO QUE SÍ SE PUEDE HACER AHORA (Post-auditoría)

✅ Gestionar catálogos (clientes, cuentas, monedas, bancos) manualmente
✅ Publicar tasas del día
✅ Ver historial de operaciones (lista y detalle básico)
✅ Verificar operaciones manualmente
✅ Exportar reportes de comisiones
✅ Demo a stakeholders

## 9. LO QUE NO SE PUEDE HACER TODAVÍA

❌ Crear operaciones reales desde el frontend Vue (formulario incompleto)
❌ Ver movimientos contables en detalle de operación (Vue no muestra tabla)
❌ Operar sin supervisión admin (sin rate limiting ni 2FA)
❌ Importar historial de Excel (Fase 7 pendiente)
❌ Calcular FIFO (Fase 4-C pendiente)
❌ Ver tasas BCV/Binance en dashboard (Fase 3 pendiente)
❌ Cumplir normativa UIF/SUDEBAN (sin KYC ni reportes AML)
❌ App móvil en manos de operadores (Flutter compilado pero no distribuido)

---

## 10. RECOMENDACIONES INMEDIATAS DEL CTO (Priorizadas)

| Prioridad | Acción | Tiempo | Responsable |
|---|---|---|---|
| P0 | Rotar credenciales expuestas (DB + admin) y limpiar Git | 2 horas | DevOps |
| P0 | Verificar `APP_DEBUG=false` en producción | 15 min | Backend |
| P0 | Agregar rate limiting a login | 2 horas | Backend |
| P1 | Decidir y fix `tipo_codigo` vs `tipo_operacion_id` | 4 horas | Arquitecto |
| P1 | Reescribir formulario de operaciones Vue con stepper y movimientos | 1 semana | Frontend |
| P1 | Agregar tabla de movimientos y comisiones al detalle de operación | 2 días | Frontend |
| P2 | Implementar 2FA | 1 semana | Backend |
| P2 | Cifrar datos sensibles de clientes | 3 días | Backend |
| P2 | Dashboard con resumen del día y alertas | 3 días | Frontend |
| P3 | Diseñar e implementar FIFO | 2 semanas | Backend Senior |
| P3 | Importador Excel | 2 semanas | Backend |
| P3 | Tests automatizados | 2 semanas | QA + Backend |

---

## 11. EQUIPO RECOMENDADO

Para ejecutar el roadmap en 2026:

| Rol | Cantidad | Tiempo | Función |
|---|---|---|---|
| Backend Senior (PHP/Laravel) | 1 | Full-time | FIFO, importador, arquitectura |
| Backend Mid (PHP/Laravel) | 1 | Full-time | Integraciones externas, reportes, tests |
| Frontend Senior (Vue/Flutter) | 1 | Full-time | Fix formularios, dashboard, app móvil |
| QA / Automation Engineer | 1 | Medio | Tests PHPUnit, E2E, pentest interno |
| DevOps / SysAdmin | 1 | Medio | Infraestructura, backups, CI/CD, seguridad |
| Compliance / Legal advisor | 1 | Consultor | KYC, AML, políticas de privacidad, UIF |

---

*Informe v2.0 preparado por el área de Tecnología de Intermedius Group.*
*Basado en revisión de: código fuente, documentación de contexto (`intermedius-contexto-proyecto.md`, `intermedius-contexto-frontend-flutter.md`, `intermedius-funcionalidades-pantallas.md`), y estado de deploy actual.*
*Próxima revisión: Junio 2026.*
