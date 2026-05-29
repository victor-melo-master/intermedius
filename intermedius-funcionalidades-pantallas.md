# Intermedius — Funcionalidades por pantalla
## Para: Johan Davila — Frontend Flutter
## Fecha: Mayo 2026

---

## Cómo leer este documento

Cada pantalla tiene:
- **Qué ve el usuario** — descripción visual
- **Qué puede hacer** — acciones disponibles
- **API que consume** — endpoints exactos
- **Roles que acceden** — quién ve esta pantalla

---

## MÓDULO 1: AUTENTICACIÓN

---

### Pantalla 1.1 — Login

**Qué ve el usuario:**
- Logo de Intermedius
- Campo email
- Campo password (con toggle mostrar/ocultar)
- Botón "Ingresar"
- Mensaje de error si las credenciales son incorrectas

**Qué puede hacer:**
- Ingresar email y password
- Ver/ocultar password
- Hacer login → si es exitoso, guarda el token y redirige al dashboard según su rol
- Si las credenciales son incorrectas → muestra error "Email o contraseña incorrectos"

**API que consume:**
```
POST /api/v1/auth/login
Body: { "email": "...", "password": "..." }
```

**Roles:** Todos (es pública, no requiere token)

**Notas:**
- Guardar el token en `flutter_secure_storage` (nunca en SharedPreferences sin cifrado)
- Guardar también el objeto `user` (nombre, email, roles) para usarlo en toda la app
- Si el usuario ya tiene token guardado y es válido → saltar directamente al dashboard

---

### Pantalla 1.2 — Splash / Verificación de sesión

**Qué ve el usuario:**
- Logo de Intermedius con animación de carga (1-2 segundos)

**Qué hace internamente:**
- Lee el token guardado en storage
- Hace `GET /api/v1/auth/me` para verificar si el token sigue válido
- Si válido → redirige al dashboard
- Si inválido o no existe → redirige al login

**API que consume:**
```
GET /api/v1/auth/me
```

---

## MÓDULO 2: DASHBOARD

---

### Pantalla 2.1 — Dashboard General

**Qué ve el usuario:**
- Encabezado con nombre del usuario y botón de logout
- Sección "Tasas del día":
  - Para cada par (USD/VES, USDT/VES): tasa de compra y tenta venta publicadas por el admin
  - Hora de última actualización
  - Si no hay tasa publicada hoy: alerta naranja "⚠️ Falta publicar tasa del día"
- Sección "Referencia de mercado" (cuando esté implementado en backend — Fase 3):
  - Tasa BCV del día
  - Tasa Binance P2P compra / venta
  - Spread entre tasa propia vs mercado
- Sección "Resumen del día":
  - Total de operaciones registradas hoy
  - Total USD movidos hoy
  - Ganancia bruta del día
  - Ganancia neta del día
- Sección "Alertas":
  - Operaciones pendientes de verificar (contador con badge rojo)
  - Operaciones con `sin_tasa_referencia = true` (operaron sin tasa del día)

**Qué puede hacer:**
- Ver toda la información (solo lectura)
- Click en "Operaciones pendientes" → navega a lista de operaciones filtrada por estatus "sin_verificar"
- Botón "Publicar tasa del día" (solo admin/super_admin) → abre modal de publicar tasa
- Botón logout → llama API logout, borra token, redirige a login

**API que consume:**
```
GET /api/v1/configuracion/tasas-vigentes
GET /api/v1/operaciones?fecha=hoy&per_page=5  (resumen)
```

**Roles:** Todos los roles autenticados

---

### Pantalla 2.2 — Publicar Tasa del Día (Modal/Bottom Sheet)

**Qué ve el usuario:**
- Selector de par de monedas (USD/VES, USDT/VES, EUR/VES, etc.)
- Campo "Tasa de compra" (lo que la casa paga al cliente)
- Campo "Tasa de venta" (lo que la casa cobra al cliente)
- Campo "Notas" (opcional, obligatorio si tasa venta < tasa compra)
- Botón "Publicar"
- Si ya hay una tasa vigente: muestra la tasa anterior con aviso "Reemplazará la tasa actual de X"

**Qué puede hacer:**
- Ingresar los valores de compra y venta
- Publicar la tasa → el sistema cierra la tasa anterior y crea la nueva
- Si tasa venta < tasa compra sin notas → error de validación
- Al publicar exitosamente → el dashboard se refresca con la nueva tasa

**API que consume:**
```
GET /api/v1/configuracion/tasas-vigentes  (para mostrar tasa actual)
POST /api/v1/configuracion/tasas-diarias
Body: {
  "fecha": "2026-05-19",
  "moneda_base_id": 2,
  "moneda_cotizada_id": 1,
  "tasa_compra": 36.40,
  "tasa_venta": 36.55,
  "notas": "Tasa del día"
}
```

**Roles:** admin, super_admin

---

## MÓDULO 3: OPERACIONES

---

### Pantalla 3.1 — Lista de Operaciones

**Qué ve el usuario:**
- Barra de búsqueda (por referencia, cliente, descripción)
- Filtros:
  - Por fecha (desde / hasta)
  - Por tipo de operación (compra USD, venta USD, cambio, gasto)
  - Por estatus (verificado, en_revision, sin_verificar)
- Lista de operaciones con cards que muestran:
  - Fecha y hora
  - Tipo de operación (con ícono de color)
  - Nombre del cliente (si aplica)
  - Monto principal en USD
  - Ganancia neta
  - Badge de estatus (verde=verificado, amarillo=en revisión, rojo=sin verificar)
- Botón flotante "+" → ir a registrar nueva operación (operador/admin)
- Paginación o infinite scroll

**Qué puede hacer:**
- Buscar y filtrar operaciones
- Click en una operación → ver detalle
- Botón "+" → registrar nueva operación
- Pull to refresh para recargar

**API que consume:**
```
GET /api/v1/operaciones?fecha_desde=...&fecha_hasta=...&tipo=...&estatus=...&page=1
```

**Roles:** Todos los autenticados (operador solo ve sus propias operaciones)

---

### Pantalla 3.2 — Detalle de Operación

**Qué ve el usuario:**
- Encabezado: tipo de operación, fecha, estatus con badge
- Sección "Datos generales":
  - Cliente (si aplica)
  - Operador que la registró
  - Referencia
  - Descripción/notas
  - Tasa sugerida del día
  - Tasa aplicada (si es distinta, resaltar en naranja)
  - Flag "⚠️ Sin tasa de referencia" si aplica
- Sección "Movimientos":
  - Tabla con cada movimiento: cuenta, monto, moneda
  - Los negativos (egresos) en rojo, los positivos (ingresos) en verde
- Sección "Resultado económico":
  - Ganancia bruta USD / VES
  - Total comisiones USD / VES
  - Ganancia neta USD / VES
- Sección "Comisiones aplicadas":
  - Lista de comisiones: descripción, tipo, monto
  - Botón "Editar" por comisión (solo admin/super_admin)
- Botón "Verificar operación" (contador/admin/super_admin, solo si estatus != verificado)
- Botón "Eliminar" (solo admin/super_admin, con confirmación)

**Qué puede hacer:**
- Ver todos los datos de la operación
- Verificar la operación (cambia estatus a "verificado")
- Editar una comisión (abre modal con campo monto + razón obligatoria)
- Eliminar la operación (con diálogo de confirmación doble)

**API que consume:**
```
GET /api/v1/operaciones/{id}
GET /api/v1/operaciones/{id}/comisiones
PATCH /api/v1/operaciones/{id}/verificar
PATCH /api/v1/operaciones/{id}/comisiones/{comision_id}
  Body: { "monto": 5.00, "razon_edicion": "Ajuste por..." }
DELETE /api/v1/operaciones/{id}
```

**Roles:** Todos (con restricciones por acción según rol)

---

### Pantalla 3.3 — Registrar Operación

Esta es la pantalla más compleja y la más importante del sistema.

**Paso 1 — Seleccionar tipo de operación:**
- Cards grandes con ícono y nombre:
  - 💵 Venta de USD (la casa vende USD al cliente, recibe VES)
  - 💰 Compra de USD (la casa compra USD del cliente, entrega VES)
  - 🔄 Cambio multimoneda (EUR↔USD, USDT↔USD, etc.)
  - 📤 Traslado interno (entre cuentas propias)

**Paso 2 — Datos básicos:**
- Cliente (buscador con autocomplete por nombre)
- Referencia (campo texto, opcional)
- Descripción/notas (campo texto, opcional)
- Fecha (por defecto hoy, editable)

**Paso 3 — Monto y tasa (según tipo seleccionado):**

Para VENTA DE USD:
- Monto en USD que entrega la casa
- Tasa de venta sugerida (se carga automáticamente del API)
- Campo editable "Tasa efectiva" (prellenado con la sugerida)
  - Si el usuario cambia a un valor MENOR → error rojo "La tasa debe ser ≥ X para ser favorable"
  - Si el usuario cambia a un valor MAYOR → OK (la casa gana más)
- Total en VES calculado automáticamente (monto × tasa efectiva)

Para COMPRA DE USD:
- Monto en USD que recibe la casa
- Tasa de compra sugerida (se carga automáticamente)
- Campo editable "Tasa efectiva"
  - Si el usuario cambia a un valor MAYOR → error rojo "La tasa debe ser ≤ X para ser favorable"
  - Si el usuario cambia a un valor MENOR → OK (la casa paga menos)
- Total en VES calculado automáticamente

Para CAMBIO MULTIMONEDA:
- Selector de par (EUR/USD, USDT/VES, etc.)
- Monto que recibe la casa (moneda origen)
- Monto que entrega la casa (moneda destino)
- Tasa implícita calculada automáticamente

**Paso 4 — Movimientos (cuentas):**
- Para cada lado de la operación (ingreso y egreso):
  - Selector de cuenta (buscador de cuentas por alias o banco)
  - Monto (prellenado del paso anterior, editable)
  - Moneda (se infiere de la cuenta seleccionada)
- Botón "Agregar movimiento" para operaciones con múltiples cuentas

**Paso 5 — Resumen y confirmación:**
- Resumen completo de la operación
- Comisiones que se aplicarán (calculadas en preview)
- Ganancia bruta y neta estimada
- Botón "Confirmar y registrar"
- Botón "Atrás" para editar

**Al confirmar:**
- Spinner de carga
- Si éxito → muestra pantalla de "Operación registrada" con número de referencia
- Si error de validación → muestra el error específico (ej: "La tasa no es favorable")
- Si error de tasa no configurada → "No hay tasa del día para USD/VES. Contacte al administrador."

**API que consume:**
```
GET /api/v1/configuracion/tasas-vigentes  (para precargar tasa sugerida)
GET /api/v1/clientes?search=...           (para buscador de clientes)
GET /api/v1/cuentas                       (para selector de cuentas)
POST /api/v1/operaciones
Body: {
  "tipo_operacion_id": 1,
  "cliente_id": 45,
  "fecha": "2026-05-19",
  "tasa_aplicada": 36.55,
  "descripcion": "Venta de USD a cliente",
  "referencia": "REF-001",
  "movimientos": [
    { "cuenta_id": 3, "monto": -100.00, "moneda_id": 2 },
    { "cuenta_id": 7, "monto": 3655.00, "moneda_id": 1 }
  ]
}
```

**Roles:** operador, admin, super_admin

---

## MÓDULO 4: CATÁLOGOS

---

### Pantalla 4.1 — Clientes

**Qué ve el usuario:**
- Barra de búsqueda por nombre o alias
- Lista de clientes con: nombre, alias, saldo actual
- Botón "+" para agregar cliente

**Qué puede hacer:**
- Buscar clientes
- Ver detalle de cliente (historial de operaciones, saldo)
- Agregar nuevo cliente
- Editar cliente existente

**API que consume:**
```
GET /api/v1/clientes?search=...
POST /api/v1/clientes
PUT /api/v1/clientes/{id}
```

---

### Pantalla 4.2 — Cuentas

**Qué ve el usuario:**
- Lista agrupada por titular (Ale, Karol, Sarah, etc.)
- Cada cuenta muestra: alias, banco, moneda, saldo actual
- Semáforo de disponibilidad (verde=disponible, rojo=sin fondos)
- Botón "+" para agregar cuenta

**Qué puede hacer:**
- Ver saldo de cada cuenta
- Agregar nueva cuenta
- Editar cuenta (alias, banco, titular)
- Activar/desactivar cuenta

**API que consume:**
```
GET /api/v1/cuentas
POST /api/v1/cuentas
PUT /api/v1/cuentas/{id}
```

---

### Pantallas 4.3 a 4.6 — Titulares, Bancos, Monedas, Categorías de gasto

Misma estructura para todos:
- Lista con búsqueda
- Botón "+" para agregar
- Click en ítem → editar
- Deslizar → eliminar (con confirmación)

**APIs:**
```
GET/POST/PUT/DELETE /api/v1/titulares
GET/POST/PUT/DELETE /api/v1/bancos
GET/POST/PUT/DELETE /api/v1/monedas
GET/POST/PUT/DELETE /api/v1/categorias-gasto
```

---

## MÓDULO 5: CONFIGURACIÓN (solo admin/super_admin)

---

### Pantalla 5.1 — Historial de Tasas

**Qué ve el usuario:**
- Selector de par de monedas
- Lista cronológica de tasas publicadas con:
  - Fecha y hora de publicación
  - Quién la publicó
  - Tasa compra / venta
  - Vigente desde / hasta

**API que consume:**
```
GET /api/v1/configuracion/tasas-diarias/historial/{base}/{cotizada}
```

---

### Pantalla 5.2 — Comisiones

**Qué ve el usuario:**
- Tabs: "Por cuenta" | "Por operador" | "Por método de pago"
- Lista de comisiones activas con: descripción, tipo de cálculo, valor, vigencia
- Botón "+" para agregar

**Qué puede hacer:**
- Ver comisiones activas
- Agregar nueva comisión
- Editar comisión existente
- Desactivar comisión (no se elimina físicamente)

**API que consume:**
```
GET/POST /api/v1/configuracion/comisiones-cuenta
GET/POST /api/v1/configuracion/comisiones-operador
GET/POST /api/v1/configuracion/comisiones-metodo-pago
```

---

## MÓDULO 6: REPORTES

---

### Pantalla 6.1 — Reportes de Comisiones por Operador

**Qué ve el usuario:**
- Selector de período (desde / hasta) con atajos: "Este mes", "Mes anterior"
- Tabla resumen por operador:
  - Nombre del operador
  - Total operaciones
  - Total comisiones en USD
- Botón "Exportar Excel"
- Botón "Exportar PDF"

**Qué puede hacer:**
- Seleccionar período
- Ver tabla resumen
- Descargar Excel → el API retorna un archivo
- Descargar PDF → el API retorna un archivo

**API que consume:**
```
GET /api/v1/reportes/comisiones-operadores?desde=2026-05-01&hasta=2026-05-31
POST /api/v1/reportes/comisiones-operadores/exportar
  Body: { "desde": "2026-05-01", "hasta": "2026-05-31", "formato": "excel" }
```

**Roles:** admin, super_admin, contador

---

## MÓDULO 7: GESTIÓN DE USUARIOS (solo super_admin)

---

### Pantalla 7.1 — Lista de Usuarios

**Qué ve el usuario:**
- Lista de usuarios con: nombre, email, rol, estado (activo/inactivo)
- Botón "+" para crear usuario

**Qué puede hacer:**
- Ver todos los usuarios del sistema
- Crear nuevo usuario
- Editar usuario (nombre, email, rol, activo)
- Desactivar usuario (no eliminar)

**Nota:** Este módulo requiere endpoints de usuarios que aún no están documentados en el API. Johan debe coordinar con el backend para definirlos o implementarlos.

---

## RESUMEN — Pantallas por módulo

| # | Pantalla | Roles | Prioridad |
|---|---|---|---|
| 1.1 | Login | Todos | 🔴 Alta |
| 1.2 | Splash | Todos | 🔴 Alta |
| 2.1 | Dashboard General | Todos | 🔴 Alta |
| 2.2 | Publicar Tasa (modal) | admin, super_admin | 🔴 Alta |
| 3.1 | Lista de Operaciones | Todos | 🔴 Alta |
| 3.2 | Detalle de Operación | Todos | 🔴 Alta |
| 3.3 | Registrar Operación | operador, admin, super_admin | 🔴 Alta |
| 4.1 | Clientes | admin, super_admin | 🟡 Media |
| 4.2 | Cuentas | admin, super_admin | 🟡 Media |
| 4.3 | Titulares | admin, super_admin | 🟡 Media |
| 4.4 | Bancos | admin, super_admin | 🟡 Media |
| 4.5 | Monedas | admin, super_admin | 🟡 Media |
| 4.6 | Categorías de gasto | admin, super_admin | 🟡 Media |
| 5.1 | Historial de tasas | admin, super_admin | 🟡 Media |
| 5.2 | Comisiones | admin, super_admin | 🟡 Media |
| 6.1 | Reportes comisiones | admin, super_admin, contador | 🟢 Baja |
| 7.1 | Gestión de usuarios | super_admin | 🟢 Baja |

---

## Navegación sugerida

```
Splash
  └─ Login
       └─ Dashboard
            ├─ [FAB] Registrar Operación
            │         ├─ Paso 1: Tipo
            │         ├─ Paso 2: Datos básicos
            │         ├─ Paso 3: Monto y tasa
            │         ├─ Paso 4: Cuentas
            │         └─ Paso 5: Confirmación
            ├─ Lista de Operaciones
            │         └─ Detalle de Operación
            ├─ Catálogos
            │         ├─ Clientes
            │         ├─ Cuentas
            │         ├─ Titulares
            │         ├─ Bancos
            │         ├─ Monedas
            │         └─ Categorías de Gasto
            ├─ Configuración (admin)
            │         ├─ Tasas: Historial
            │         └─ Comisiones
            └─ Reportes (admin/contador)
                      └─ Comisiones por operador
```

---

## Consideraciones de UX importantes

1. **Formateo de monedas:** Siempre mostrar VES con separadores de miles y 2 decimales. USD con símbolo "$". USDT con símbolo "₮" o "USDT".

2. **Tasas:** Siempre mostrar con 2-4 decimales. Ej: "36.55 VES/USD".

3. **Estados de operación:**
   - Verde → verificado
   - Amarillo → en revisión
   - Rojo → sin verificar

4. **Feedback visual:** Cada acción que llama al API debe tener spinner de carga. Los errores del API deben mostrarse en un snackbar o dialog legible.

5. **Pantalla de Registrar Operación:** Usar un stepper (paso a paso) para no abrumar al operador. Esta es la pantalla que más usarán.

6. **Modo offline:** No es necesario para v1, pero los formularios deben guardar estado si el usuario navega hacia atrás accidentalmente.

7. **Responsive:** La app debe funcionar bien en móvil (teléfono) y en web (tablet/desktop). El dashboard y las listas de operaciones se benefician de layouts distintos según el ancho de pantalla.
