# Análisis de procesos — Sistema de control financiero (casa de cambio / remesas)

Documento base para el diseño de un API que replique el comportamiento del libro Excel
`IG_ENERO_KMSA20_01`. Se derivó inspeccionando las fórmulas reales de cada hoja (no solo los
valores), para capturar la lógica de negocio, no solo los datos de ejemplo.

---

## 1. Contexto del negocio

El archivo controla la operación de una **casa de cambio / remesadora informal** que mueve
dinero entre:

- **Clientes** (personas que compran, venden, pagan o retiran)
- **Bancos** (cuentas bancarias propias, en bolívares — Bs)
- **Plataformas** (billeteras/wallets en USD u otras monedas: Zelle, Zinli, Trust Wallet, Cash, etc.)
- **Cambios** (operaciones de arbitraje/canje entre divisas, ej. EUR→USD)

El negocio gana dinero por:
1. **Spread cambiario** (diferencia entre tasa de compra y venta de USD/Bs)
2. **Comisiones** fijas (0.3% en operaciones en bolívares)
3. **Diferencias en operaciones de cambio** (CAMBIOS: recibido vs. enviado, menos costos de envío)

Cada "movimiento" registrado en una hoja transaccional dispara actualizaciones automáticas en
saldos de clientes, bancos y plataformas (equivalente a un **ledger de partida doble**).

---

## 2. Entidades maestras (catálogos → hoja `LISTAS`)

Estas listas alimentan validaciones (dropdowns) y deben modelarse como tablas de referencia:

| Catálogo | Ejemplos de valores |
|---|---|
| `ORIGEN` / `DESTINO` | BANCOS, CLIENTES, PLATAFORMAS, GASTOS |
| `CLIENTES` | EVELIO RAMIREZ, JOHANA RODRIGUEZ, KAROL MALDONADO, SARAH ALVAREZ... |
| `BANCOS` | BAN ALE, BAN ANA K, BAN BEA, VZLA JOHA, MERC KAR, BANCA KAR, etc. (30 cuentas) |
| `MONEDAS` | BS, CASH, PANAMA, ZINLI, EUROS, USDT, BOFA, ZELLE, FACEBANK |
| `PLATAFORMAS` | CASH SARAH, ZELLE ITSIA, TRUST WALLET I-IV, BNB, ZINLI, etc. |
| `GASTOS` | AHORRO, ALQUILER, COMISION, NOMINA, OPERATIVOS, OTROS |
| `CAMBIOS` (pares) | EUR/USD, USD/EUR, USDT/CASH, ZELLE/CASH, etc. |
| `EMPLEADOS` (para nómina/comisiones) | KAROL MALDONADO, MIGUEL RAMONES, SARAH ALVAREZ, SERGIO LOPEZ, YENSI RIOS, EDUARD OCHOA, BEATRIZ TORREALBA, ANA KARINA MORENO |

**Recomendación de API:** exponer estos catálogos como recursos propios
(`/catalogs/origenes`, `/catalogs/clientes`, `/catalogs/bancos`, `/catalogs/plataformas`,
`/catalogs/gastos`, `/catalogs/empleados`) para que el front-end arme los selects, igual que
hace Excel con `Data Validation`.

---

## 3. Hojas transaccionales (tablas de movimientos)

### 3.1 `DOLARES` — Movimientos en USD (Tabla12)

Registra transferencias de dólares entre origen y destino.

**Columnas de entrada (lo que un usuario captura):**

| Campo | Tipo | Notas |
|---|---|---|
| FECHA | date | |
| ORIGEN2 | enum(`ORIGEN`) | BANCOS / CLIENTES / PLATAFORMAS |
| EMISOR2 | enum(según ORIGEN2) | quién envía |
| MONEDA | enum(`MONEDAS`) | CASH, EUR, USDT... |
| MONTO | decimal | monto enviado |
| DESTINO | enum(`ORIGEN`) | |
| RECEPTOR | enum(según DESTINO) | quién recibe |
| MONTO2 | decimal | = MONTO (fórmula `=[MONTO]`, es decir, réplica; útil para casos donde el monto recibido difiera del enviado) |
| REMITENTE / RECEPTOR2 | texto libre | opcionales |

**Reglas derivadas (columnas calculadas / verificadores):**

```
USD_RECIBIDO   = SUMIF(ORIGEN2 = "PLATAFORMAS", MONTO)
USD_PAGADO     = SUMIFS(MONTO, ORIGEN2 = "CLIENTES", DESTINO = origen_actual)
USD_PAGADO_CLIENTES = SUMIFS(MONTO, ORIGEN2 = "CLIENTES", DESTINO = "CLIENTES")

VERIFICADOR_PLATAFORMAS:
  INGRESOS = SUMIF(DESTINO = "PLATAFORMAS", MONTO2)
  EGRESOS  = SUMIF(ORIGEN2 = "PLATAFORMAS", MONTO)
  # Estos deben coincidir con los totales por plataforma (ver §4.3)
```

**Efecto lateral (side-effects) al crear un movimiento DOLARES:**
- Si `ORIGEN2 = CLIENTES`: incrementa el campo `PAGO` del cliente emisor.
- Si `DESTINO = CLIENTES`: incrementa el campo `RETIRO` del cliente receptor.
- Si `ORIGEN2 = PLATAFORMAS`: incrementa `EGRESOS` de esa plataforma.
- Si `DESTINO = PLATAFORMAS`: incrementa `INGRESOS` de esa plataforma.

---

### 3.2 `BOLIVARES` — Movimientos en Bs con tasa de cambio (Tabla `BOLIVARES`)

Es la hoja más compleja: cada fila es una operación de **compra/venta de USD contra bolívares**,
con dos "patas" (origen y destino), cada una con su propia tasa.

**Columnas de entrada:**

| Campo | Tipo | Notas |
|---|---|---|
| FECHA | date | |
| ORIGEN | enum | BANCOS / CLIENTES |
| EMISOR | enum(según ORIGEN) | |
| MONEDA | enum | normalmente "BS" |
| TASA | decimal | tasa de cambio pata 1 (Bs por USD) |
| BOLIVARES1 | decimal | monto en bolívares de la pata 1 (input) |
| COMISION | decimal (calculado) | `= BOLIVARES1 * 0.003` (0.3% fijo) |
| REF | texto | número de referencia/comprobante |
| DESTINO | enum | BANCOS / CLIENTES |
| RECEPTOR | enum(según DESTINO) | |
| MONEDA2 | enum | CASH, PANAMA, etc. |
| TASA2 | decimal | tasa pata 2 |
| BOLIVARES2 | decimal | = BOLIVARES1 (réplica, pata 2 usa el mismo monto en Bs) |
| DESCRIPCION | texto | opcional |

**Campos calculados por fila:**

```
DOLARES  = BOLIVARES1 / TASA        (si TASA = 0 → 0, vía IFERROR)
DOLARES2 = BOLIVARES2 / TASA2       (si TASA2 = 0 → 0)
COMISION = BOLIVARES1 * 0.003
GANANCIA_DIRECTA = SI(ORIGEN="CLIENTES" Y DESTINO="CLIENTES"; DOLARES2 - DOLARES; 0)
```

> **Regla de negocio clave:** cuando una operación es cliente→cliente (P2P), la ganancia
> directa es la diferencia entre lo que "vale" en USD la pata 2 vs. la pata 1 (arbitraje de tasas).

**Verificadores / reportes agregados (parte derecha de la hoja):**

```
VERIFICADOR DE USD X BOLIVARES:
  VENDIDO    = SUMIF(ORIGEN = "CLIENTES", DOLARES)
  COMPRADO   = SUMIF(DESTINO = destino_actual, DOLARES2)   # por cada tipo de destino
  DIF        = VENDIDO - COMPRADO

VERIFICADOR DE BOLIVARES EN BANCOS:
  RECIBIDOS_EN_BANCOS = SUM(BANCOS.INGRESO) - MOVIMIENTOS_INTERNOS
  ENVIADO_DESDE_BANCOS = SUM(BANCOS.EGRESO) - MOVIMIENTOS_INTERNOS
  ENVIOS_BS_CLIENTE_A_CLIENTE = SUMIFS(BOLIVARES1, ORIGEN="CLIENTES", DESTINO="CLIENTES")

VERIFICADOR DE BOLIVARES TOTALES:
  TOTAL_INGRESOS  = SUMIF(ORIGEN="CLIENTES", BOLIVARES1)
  TOTAL_EGRESOS   = SUMIF(DESTINO="CLIENTES", BOLIVARES2)
  REMANENTE       = (TOTAL_INGRESOS + ACUMULADO) - TOTAL_EGRESOS - GASTOS_OPERATIVOS - COMISIONES_PM
  ACUMULADO       = SUM(saldos de todos los bancos)   # viene de la hoja BANCOS

DISTRIBUCION DE LOS INGRESOS:
  INGRESOS_POR_VENTAS         = SUMIFS(BOLIVARES1, ORIGEN="CLIENTES", DESTINO="BANCOS")
  INGRESOS_PARA_PAGO_GASTOS   = SUMIFS(BOLIVARES1, ORIGEN="CLIENTES", DESTINO="GASTOS")
  INGRESOS_POR_PAGO_INTERESES = SUMIF(EMISOR = <cuenta especial>, BOLIVARES1)

DISTRIBUCION DE LOS EGRESOS:
  EGRESOS_POR_COMPRAS         = SUMIFS(BOLIVARES1, ORIGEN="BANCOS", DESTINO="CLIENTES")
  EGRESOS_POR_GASTOS          = SUM(subcategorías: NOMINA + CONTRATACION_SERVICIOS + OPERATIVOS + IMPUESTOS + PERDIDA + OTROS + REMODELACION)
  COMISIONES_POR_PAGO_MOVIL   = SUMIF(ORIGEN="BANCOS", COMISION)

DISTRIBUCION DE GASTOS / NOMINA / COMISIONES (por empleado):
  Para cada empleado E:
    MONTO_BS  = SUMIF(RECEPTOR = E, BOLIVARES2)
    MONTO_USD = SUMIF(RECEPTOR = E, MONTO2)   # tabla DOLARES

DISTRIBUCION DE LAS COMISIONES (por "banco de socio", solo 3 socios: KAROL, SARAH, BEATRIZ):
  Z = suma de saldos de bancos asignados a ese socio (mapeo fijo banco→socio, ver §5)
  AB = Z - nómina_asignada_al_socio
  AC = AB * 0.003              (comisión 0.3% sobre el excedente)
  AD = nómina en USD de ese socio (de tabla NOMINA)
  AE = AC - AD
  AF = AC / 300                 (conversión a USD con tasa fija 300, legado — revisar)
```

**Nota para el API:** varias fórmulas usan **tasas fijas hardcodeadas** (300, 420) que en
Excel son "mágicas" — en el API deben ser **parámetros configurables** (tabla de tasas de
referencia por fecha), no constantes en código.

---

### 3.3 `CAMBIOS` — Operaciones de canje de divisas (Tabla `CAMBIOS`)

Registra operaciones tipo "recibí X, debía entregar Y" (arbitraje/canje, ej. EUR↔USD, USDT↔Cash).

**Columnas de entrada:**

| Campo | Tipo |
|---|---|
| FECHA | date |
| CAMBIO | enum(`CAMBIOS` — par de divisas, ej. "CASH/EUR") |
| SOLICITANTES | enum(`CLIENTES`) |
| RECIBIDO | decimal |
| ENVIAR | decimal |
| COSTOS_DE_ENVIO | decimal (opcional) |
| OBSERVACIONES | texto |

**Campos calculados:**

```
DIFERENCIA     = RECIBIDO - ENVIAR
PORCENTAJE     = DIFERENCIA * 100 / ENVIAR         (0 si ENVIAR = 0)
GANANCIA_REAL  = DIFERENCIA - COSTOS_DE_ENVIO
% REAL         = (DIFERENCIA - COSTOS_DE_ENVIO) * 100 / ENVIAR
```

**Verificador:**
```
COMISIONES_RECIBIDAS = SUM(DIFERENCIA) - SUM(comisiones ya registradas en CLIENTES)
COSTOS_DE_ENVIOS     = SUM(COSTOS_DE_ENVIO)
COMPRA_TRON / DISPONIBLE_EN_TRON = seguimiento específico de una plataforma cripto (TRON),
  filtrando movimientos de DOLARES cuyo RECEPTOR = "TRON"
```

**Efecto lateral:** cada operación de CAMBIOS afecta el campo `COMISION` del cliente
solicitante (ver §3.4).

---

### 3.4 `CLIENTES` — Estado de cuenta por cliente (vista derivada, Tabla3/Tabla14)

Esta hoja **no es transaccional**: es una vista calculada a partir de `DOLARES`, `BOLIVARES` y
`CAMBIOS`. En el API debe ser un **endpoint de agregación**, no una tabla editable (salvo el
campo `PENDIENTE`, que sí es manual/inicial).

```
CLIENTE (nombre)          — clave
PENDIENTE                 — saldo inicial/manual (input humano)
COMPRA   = SUMIF(BOLIVARES.EMISOR = cliente, BOLIVARES.DOLARES)
VENTA    = SUMIF(BOLIVARES.RECEPTOR = cliente, BOLIVARES.DOLARES2)
PAGO     = SUMIF(DOLARES.EMISOR2 = cliente, DOLARES.MONTO)
RETIRO   = SUMIF(DOLARES.RECEPTOR = cliente, DOLARES.MONTO2)
COMISION = SUMIF(CAMBIOS.SOLICITANTE = cliente, CAMBIOS.DIFERENCIA)
         + SUMIF(CAMBIOS.SOLICITANTE = cliente, CAMBIOS.COSTOS_DE_ENVIO)

SALDO = PENDIENTE + VENTA + RETIRO - COMPRA - PAGO + COMISION
```

Interpretación: el saldo representa **cuánto le debe la casa al cliente (positivo) o cuánto
debe el cliente (negativo)**, combinando sus compras/ventas de USD, pagos/retiros en efectivo
y comisiones generadas por cambios.

Cada cliente también tiene metadatos: `TIPO` (categoría/apodo operativo) y `GRUPO` (agrupación
para reportes, ej. "GRUPO A").

---

### 3.5 `BANCOS` — Estado de cuentas bancarias (vista derivada, Tabla4/Tabla16)

```
BANCO (nombre)
ACUMULADO  — saldo inicial (input manual, "carry-over" del período anterior)
INGRESO = SUMIF(BOLIVARES.RECEPTOR = banco, BOLIVARES.BOLIVARES1)
EGRESO  = SUMIF(BOLIVARES.EMISOR = banco, BOLIVARES.BOLIVARES1)
        + SUMIF(BOLIVARES.EMISOR = banco, BOLIVARES.COMISION)
SALDO      = ACUMULADO + INGRESO - EGRESO
DISPONIBLE — input manual opcional (saldo real verificado, ej. de banca en línea)
DIFERENCIA = SALDO - DISPONIBLE     (cuadre / alerta de descuadre)
```

Fila de totales: suma de ACUMULADO, INGRESO, EGRESO, SALDO de todas las cuentas — es el
`ACUMULADO` global que alimenta `BOLIVARES!U19` (remanente total del negocio).

---

### 3.6 `PLATAFORMAS` — Estado de wallets/plataformas (vista derivada, Tabla10/Tabla17)

Idéntica lógica a BANCOS pero alimentada desde la tabla `DOLARES` en vez de `BOLIVARES`:

```
PLATAFORMA (nombre)
ACUMULADO — input manual
INGRESOS = SUMIF(DOLARES.RECEPTOR = plataforma, DOLARES.MONTO2)
EGRESOS  = SUMIF(DOLARES.EMISOR2 = plataforma, DOLARES.MONTO)
SALDO      = ACUMULADO + INGRESOS - EGRESOS   (fórmula implícita, ver Bancos)
DISPONIBLE — input manual
DIFERENCIA = SALDO - DISPONIBLE
```

---

## 4. Modelo de datos sugerido para el API

### 4.1 Tablas transaccionales (append-only / editable con auditoría)

```
movimientos_dolares
  id, fecha, origen_tipo, origen_id, moneda, monto,
  destino_tipo, destino_id, monto2, remitente, receptor2,
  created_by, created_at

movimientos_bolivares
  id, fecha, origen_tipo, origen_id, moneda, tasa, bolivares1,
  comision (calculado, persistir o derivar), ref,
  destino_tipo, destino_id, moneda2, tasa2, bolivares2 (=bolivares1),
  descripcion,
  dolares (derivado), dolares2 (derivado), ganancia_directa (derivado),
  created_by, created_at

movimientos_cambios
  id, fecha, par_cambio, solicitante_cliente_id, recibido, enviar,
  costos_envio, observaciones,
  diferencia (derivado), porcentaje (derivado),
  ganancia_real (derivado), porcentaje_real (derivado)
```

### 4.2 Catálogos / maestros

```
clientes        (id, nombre, tipo, grupo, pendiente_inicial)
bancos          (id, nombre, acumulado_inicial, disponible_manual, socio_asignado)
plataformas     (id, nombre, acumulado_inicial, disponible_manual)
empleados       (id, nombre)
categorias_gasto(id, nombre)  # AHORRO, ALQUILER, COMISION, NOMINA, OPERATIVOS, OTROS...
pares_cambio    (id, nombre)  # EUR/USD, USD/EUR, USDT/CASH...
```

### 4.3 Vistas / endpoints calculados (no se guardan, se derivan al vuelo o vía job)

```
GET /clientes/{id}/estado-cuenta      → compra, venta, pago, retiro, comision, saldo
GET /bancos/{id}/estado-cuenta        → ingreso, egreso, saldo, diferencia
GET /plataformas/{id}/estado-cuenta   → ingresos, egresos, saldo, diferencia
GET /reportes/verificador-usd
GET /reportes/verificador-bolivares
GET /reportes/distribucion-ingresos
GET /reportes/distribucion-egresos
GET /reportes/distribucion-nomina
GET /reportes/distribucion-comisiones     (por socio: KAROL, SARAH, BEATRIZ)
GET /reportes/comisiones-cambios
```

---

## 5. Reglas de negocio a codificar explícitamente (no solo "sumar filas")

1. **Comisión automática del 0.3%** sobre `BOLIVARES1` en toda operación de la hoja BOLIVARES
   (columna `COMISION`, se calcula server-side, no la captura el usuario).
2. **Ganancia directa** solo aplica en operaciones **cliente→cliente**; en cualquier otro
   origen/destino es 0. Esto debe ser una regla condicional explícita en el servicio, no un
   campo libre.
3. **Réplica de monto**: en DOLARES, `MONTO2` casi siempre iguala a `MONTO` (mismo valor, dos
   columnas por diseño de tabla dinámica de Excel). En BOLIVARES, `BOLIVARES2 = BOLIVARES1`.
   El API puede simplificar esto a un solo campo `monto`, salvo que el negocio realmente use
   montos distintos en origen/destino (verificar con el usuario del negocio).
4. **Asignación banco → socio** es un mapeo fijo y manual (hardcodeado en las fórmulas
   `Z42`, `Z43`, `Z44`, `Z45` de BOLIVARES) que lista qué cuentas bancarias pertenecen a cada
   socio (Karol, Sarah, Beatriz, Ana Karina). Esto **debe ser una tabla de configuración**
   (`banco_socio_map`), no lógica hardcodeada.
5. **Tasas de conversión fijas** (300, 420) usadas en varias fórmulas de remanente/promedio son
   probablemente tasas "de referencia" de un período específico — deben salir a una tabla
   `tasas_referencia(fecha, tasa)` configurable, no quedar fijas en el código.
6. **Verificadores/cuadres** (columnas T-W en BOLIVARES, M-P en DOLARES) son controles de
   integridad: comparan un total calculado por un camino contra el mismo total calculado por
   otro camino, y la diferencia debería ser 0. El API debería exponerlos como
   **endpoints de "reconciliación"** que alerten si hay descuadre, replicando el propósito de
   auditoría cruzada que tienen en el Excel.
7. **Categorías de gasto y nómina** están vinculadas por nombre de receptor contra listas fijas
   de empleados/categorías (`LISTAS!L3:L26`). Deben normalizarse a IDs de catálogo con
   relación FK, no matching por texto libre.

---

## 6. Flujo funcional resumido (para diseñar los endpoints CRUD)

```
1. Alta de catálogos: clientes, bancos, plataformas, empleados, categorías de gasto
   → POST /clientes, /bancos, /plataformas, /empleados, /categorias-gasto

2. Registro de movimientos diarios:
   a. Movimiento en USD           → POST /movimientos/dolares
   b. Movimiento en Bs con tasa   → POST /movimientos/bolivares
   c. Operación de cambio/canje   → POST /movimientos/cambios

3. Al registrar cualquier movimiento:
   - Calcular campos derivados (comisión, ganancia, diferencia) en el servidor.
   - Actualizar (o invalidar caché de) los saldos de cliente/banco/plataforma involucrados.

4. Consulta de estado:
   - Estado de cuenta por cliente/banco/plataforma (saldo en tiempo real)
   - Reportes de distribución (ingresos, egresos, nómina, comisiones)
   - Verificadores de cuadre (para detectar errores de captura)

5. Cierre de período:
   - "ACUMULADO" de bancos/plataformas se convierte en el saldo inicial del siguiente período
     (snapshot mensual, como sugiere el nombre del archivo "IG_ENERO...").
```

---

## 7. Consideraciones adicionales para el diseño del API

- **Multi-moneda:** el sistema maneja USD, Bs, EUR, USDT, y otras "monedas" que en realidad son
  plataformas (PANAMA, ZINLI, BOFA). Conviene separar claramente **moneda** (unidad de cuenta)
  de **canal/plataforma** (medio de pago), algo que el Excel mezcla en la práctica.
- **Auditoría:** dado que hay verificadores de cuadre, el API debería guardar quién y cuándo
  registró cada movimiento (campos `created_by`, `created_at`), y permitir recalcular
  reportes históricos sin mutar los movimientos originales.
- **Periodicidad:** el archivo es mensual ("ENERO"); el diseño del API debería soportar
  períodos (mes/año) como dimensión de todos los reportes y saldos "ACUMULADO".
- **Validaciones de catálogo:** todo `ORIGEN`/`DESTINO`/`EMISOR`/`RECEPTOR` debe validarse
  contra su catálogo correspondiente (igual que las listas desplegables en Excel) para evitar
  nombres inconsistentes que rompan los `SUMIF`.

---

*Este documento describe el comportamiento observado en las fórmulas del archivo Excel
proporcionado. Antes de construir el API, se recomienda validar con el dueño del negocio los
puntos marcados con "revisar" (tasas fijas, mapeo socio-banco) ya que probablemente reflejan
decisiones operativas específicas de enero 2026 y no reglas permanentes.*
