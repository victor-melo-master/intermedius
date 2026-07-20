# Procesos del Excel — Detalle exacto de qué hace cada hoja

Este documento explica, **paso a paso y con los números reales del archivo**, exactamente qué
ocurre cuando se captura cada tipo de movimiento. No es un resumen: es la traza completa de
cálculo, celda por celda, con la fórmula real y el resultado real que produce.

---

## PROCESO 1 — Registrar un movimiento de USD (hoja `DOLARES`)

### 1.1 Qué captura el usuario (fila nueva en la tabla `Tabla12`)

Ejemplo real, fila 3 del archivo:

| Campo | Valor capturado |
|---|---|
| FECHA | 15/07/2026 |
| ORIGEN2 | PLATAFORMAS |
| EMISOR2 | CASH BELKIS |
| MONEDA | CASH |
| MONTO | 1000 |
| DESTINO | CLIENTES |
| RECEPTOR | SARAH ALVAREZ |

Es decir: **"1000 USD salieron de la plataforma CASH BELKIS y llegaron al cliente SARAH ALVAREZ"**.

### 1.2 Qué calcula el Excel automáticamente al guardar esa fila

**Columna `MONTO2` (I3):**
```
I3 = Tabla12[[#This Row],[MONTO]]
```
→ Simplemente copia el mismo monto (1000). En este diseño, `MONTO` es lo que sale del
origen y `MONTO2` es lo que entra al destino — normalmente son iguales, pero la columna existe
separada por si algún día difieren (comisión de cambio en el envío, por ejemplo).

### 1.3 Qué recalculan los "verificadores" de la hoja (columnas M-P)

Estos NO dependen de la fila que acabas de meter únicamente — son **sumas de toda la tabla**,
que se recalculan cada vez que agregas/editas cualquier fila. Con los datos actuales del
archivo (2 filas: la 3 y la 4), esto es lo que arrojan:

**`USD RECIBIDO` (fila 3, columnas N-P):**
```
N3 = SUMIF(ORIGEN2 = "PLATAFORMAS", MONTO)              → 900
     (ojo: en LISTAS!B4 el valor de referencia es "CLIENTES", no "PLATAFORMAS" —
      esta fórmula en realidad filtra por el catálogo LISTAS, no por texto fijo)
O3 = SUM(PAGO de todos los clientes, tabla CLIENTES)     → 900
P3 = N3 - O3                                              → 0   (cuadra ✅)
```
Interpretación: el total de USD que entraron por la columna "recibido de plataformas" debe
coincidir exactamente con la suma de "PAGO" que aparece en la hoja CLIENTES. Si `P3 ≠ 0`,
hay un movimiento mal capturado en algún lado.

**`USD PAGADO` (fila 4):**
```
N4 = SUMIFS(MONTO; ORIGEN2="CLIENTES"; DESTINO=origen_de_la_lista)   → 1000
O4 = SUM(RETIRO de todos los clientes, tabla CLIENTES)                → 1900
P4 = (N4 + N5) - O4                                                    → 0   (cuadra ✅)
```

**`USD PAGADO CLIENTES` (fila 5):**
```
N5 = SUMIFS(MONTO; ORIGEN2="CLIENTES"; DESTINO="CLIENTES")   → 900
```
Este es el sub-total de dinero que se movió **entre clientes** (no banco/plataforma de por
medio) — en el ejemplo: la fila 4 (SARAH → EVELIO, 900 EUR).

**`VERIFICADOR DE PLATAFORMAS` (filas 9-10):**
```
INGRESOS: N9 = SUMIF(DESTINO="PLATAFORMAS", MONTO2)             → 0
          O9 = Total de INGRESOS en la hoja PLATAFORMAS          → 0
          P9 = N9 - O9                                            → 0  (cuadra ✅)

EGRESOS:  N10 = SUMIF(ORIGEN2="PLATAFORMAS", MONTO)              → 1000
          O10 = Total de EGRESOS en la hoja PLATAFORMAS           → 0
          P10 = N10 - O10                                          → 1000  (⚠️ NO cuadra)
```
> Este descuadre de 1000 es real en el archivo de ejemplo: la plataforma "CASH BELKIS" tiene
> un egreso de 1000 registrado en la hoja PLATAFORMAS, pero el verificador de DOLARES espera
> que el total de "ORIGEN2 = PLATAFORMAS" (1000) sea igual al total de "EGRESOS" reportado en
> PLATAFORMAS (0 en la celda O10 porque la fórmula ahí SUMA mal — apunta a un total vacío).
> **Esto es exactamente el tipo de error que el API debe prevenir con validaciones, no solo
> detectar después.**

### 1.4 Efecto en cascada — qué otras hojas cambian solas

Al guardar la fila 3 de DOLARES, sin que el usuario toque nada más, cambian automáticamente:

1. **Hoja `CLIENTES`, fila de SARAH ALVAREZ, columna `RETIRO`:**
   ```
   G(SARAH) = SUMIF(Tabla12[RECEPTOR], "SARAH ALVAREZ", Tabla12[MONTO2]) = 1000
   ```
2. **Hoja `PLATAFORMAS`, fila de CASH BELKIS, columna `EGRESOS`:**
   ```
   E(CASH BELKIS) = SUMIF(Tabla12[EMISOR2], "CASH BELKIS", Tabla12[MONTO]) = 1000
   ```
3. La fila 4 (SARAH → EVELIO) hace lo mismo pero como origen y destino son ambos "CLIENTES",
   afecta el `PAGO` de SARAH y el `RETIRO` de EVELIO simultáneamente:
   ```
   F(SARAH) = SUMIF(EMISOR2, "SARAH ALVAREZ", MONTO) = 900   → columna PAGO
   G(EVELIO) = SUMIF(RECEPTOR, "EVELIO RAMIREZ", MONTO2) = 900 → columna RETIRO
   ```

**Regla para el API:** un movimiento DOLARES es una transacción atómica que debe:
1. Insertar la fila.
2. Recalcular (o marcar para recalcular) el saldo del cliente/plataforma/banco de origen.
3. Recalcular el saldo del cliente/plataforma/banco de destino.
4. Recalcular los verificadores globales de la hoja (para exponerlos en un endpoint de
   "salud"/reconciliación).

---

## PROCESO 2 — Registrar un movimiento en Bolívares con tasa (hoja `BOLIVARES`)

Este es el proceso más importante y el más complejo. Modela una operación de **compra o venta
de bolívares contra dólares**, con posibilidad de que el origen y el destino usen tasas
distintas.

### 2.1 Qué captura el usuario

Ejemplo real, fila 3:

| Campo | Valor |
|---|---|
| FECHA | 19/01/2026 |
| ORIGEN | BANCOS |
| EMISOR | VZLA JOHA |
| MONEDA | BS |
| BOLIVARES1 | 19500 |
| REF | 5271 |
| DESTINO | CLIENTES |
| RECEPTOR | EVELIO RAMIREZ |
| MONEDA2 | CASH |
| TASA2 | 390 |

Traducción: **"Del banco VZLA JOHA salieron 19.500 Bs; llegaron al cliente EVELIO RAMIREZ, quien
los recibe convertidos a USD (CASH) a una tasa de 390 Bs/USD"**.

> Nota: en esta fila **no** se llenó `TASA` (columna G) — solo `TASA2`. Esto es válido: la
> tasa de origen solo aplica cuando el ORIGEN es "CLIENTES" (venta del cliente al negocio);
> cuando el origen es un banco, la tasa relevante es la de salida (TASA2).

### 2.2 Cálculos automáticos por fila (se disparan al guardar)

```
F3  (DOLARES)     = IFERROR(BOLIVARES1 / TASA, 0)         = IFERROR(19500/0, 0)  = 0
                    (no hay TASA en esta fila → resultado 0, correcto: no es venta de cliente)

I3  (COMISION)    = BOLIVARES1 * 0.003                     = 19500 * 0.003 = 58.5
                    (0.3% de comisión, se cobra SIEMPRE sobre el monto en bolívares
                     de la pata 1, sin importar el tipo de operación)

N3  (DOLARES2)    = IFERROR(BOLIVARES2 / TASA2, 0)          = 19500 / 390 = 50
                    (el cliente recibe el equivalente de 19.500 Bs en USD a 390 = 50 USD)

P3  (BOLIVARES2)  = BOLIVARES1                               = 19500
                    (réplica: el monto en bolívares no cambia entre pata 1 y pata 2)

R3  (GANANCIA DIRECTA) = SI(ORIGEN="CLIENTES" Y DESTINO="CLIENTES"; DOLARES2-DOLARES; 0)
                        = SI(BANCOS="CLIENTES" Y CLIENTES="CLIENTES"; ...; 0)
                        = 0   (porque ORIGEN es "BANCOS", no "CLIENTES" → no aplica)
```

**Regla de negocio a memorizar:** `GANANCIA DIRECTA` solo se activa cuando el negocio hace de
intermediario **entre dos clientes** (compra a un cliente y vende a otro cliente en la misma
operación). Cuando el origen es un banco o el destino es un banco, la ganancia se calcula por
otro camino (spread de tasas en los reportes agregados, no en esta columna).

### 2.3 Ejemplo de una fila donde SÍ hay ganancia directa (cliente↔cliente)

Tomemos un caso hipotético con los mismos catálogos: si `ORIGEN=CLIENTES`, `EMISOR=KAROL`,
`TASA=500`, `BOLIVARES1=50000` (fila 7 real), y `DESTINO=CLIENTES`, `RECEPTOR=SARAH`,
`TASA2=400`:

```
F7  (DOLARES)  = 50000 / 500 = 100      → lo que "vale" para KAROL (le compramos a 500)
N7  (DOLARES2) = 50000 / 400 = 125      → lo que "vale" para SARAH (le vendemos a 400)
R7  (GANANCIA DIRECTA) = 125 - 100 = 25 USD
```
El negocio compró bolívares "caro" (tasa 500, menos USD por Bs) y se los entregó a otro
cliente "barato" (tasa 400, más USD por Bs) → pierde 25 USD en esa operación puntual (si tasa2
> tasa1, el receptor obtiene MÁS dólares por los mismos bolívares, lo cual es una pérdida para
el negocio, no una ganancia — hay que validar el signo con el dueño del negocio antes de
replicarlo en el API literal, porque depende de qué lado se mira el margen).

### 2.4 Los "verificadores de cuadre" — qué hacen exactamente

Estos son controles cruzados: **el mismo número, calculado por dos caminos distintos**, debe
dar la misma cifra. Ejemplo real:

**`RECIBIDOS EN BANCOS` (fila 7, columnas T-W):**
```
U7 = SUM(INGRESO de todos los bancos) - AB3           = 100000 - 0   = 100000
V7 = Z3 (INGRESOS POR VENTAS, calculado abajo)                        = 100000
W7 = U7 - V7                                                            = 0   ✅ Cuadra
```
Camino 1 (U7): suma directamente la columna INGRESO de la hoja BANCOS (que a su vez es
`SUMIF(RECEPTOR=banco, BOLIVARES1)` sobre toda la tabla BOLIVARES).
Camino 2 (V7): filtra directamente en BOLIVARES cuánto tiene ORIGEN=CLIENTES y DESTINO=BANCOS.
Si ambos caminos no dan lo mismo, **hay una fila con ORIGEN o DESTINO mal catalogado**.

**`ENVIADO DESDE BANCOS` (fila 8):**
```
U8 = SUM(EGRESO de todos los bancos) - AB3            = 130700 - 0 = 130700
V8 = SUM(Z8:Z10) - Z4                                                = 130700
W8 = U8 - V8                                                          = 0   ✅ Cuadra
```

**`TOTAL INGRESOS` / `TOTAL EGRESOS` / `REMANENTE` / `ACUMULADO` (filas 14, 15, 17, 19):**
```
TOTAL_INGRESOS (U14) = SUMIF(ORIGEN="CLIENTES", BOLIVARES1)           = 150000
TOTAL_EGRESOS  (U15) = SUMIF(DESTINO="CLIENTES", BOLIVARES2)          = 180700
REMANENTE      (U17) = (TOTAL_INGRESOS + ACUMULADO) - TOTAL_EGRESOS
                        - GASTOS_OPERATIVOS - COMISIONES_PM
                      = (150000 + 5271610.61) - 180700 - 0 - 0
                      = 5240910.61
ACUMULADO      (U19) = Total de la columna ACUMULADO en la hoja BANCOS = 5271610.61
```
**Este es el número más importante del archivo**: `REMANENTE` es literalmente el patrimonio
en bolívares que le queda al negocio, sumando lo que había + lo que entró - lo que salió.

**`DIF` en varios lugares (filas 29, 31):** son alertas de descuadre entre "lo que se compró"
vs "lo que se vendió" en un sub-proceso. Ejemplo:
```
U28 (USD COMPRADOS) = SUMIFS(DOLARES2; ORIGEN="BANCOS"; DESTINO="CLIENTES")   = 330
U27 (DOLARES VENDIDOS PARA TRABAJAR) = 0
U29 (DIF) = U28 - U27 = 330    ← diferencia pendiente de explicar/conciliar
```

### 2.5 Distribución de ingresos y egresos — proceso de "clasificación automática"

Cada bolívar que entra o sale se clasifica automáticamente según **quién es el origen o
destino**, sin que el usuario tenga que elegir una categoría manualmente:

```
INGRESOS POR VENTAS (Y3/Z3)
  = SUMIFS(BOLIVARES1; ORIGEN="CLIENTES"; DESTINO="BANCOS")     = 100000
  → "el cliente entregó bolívares que fueron a parar a un banco del negocio"

INGRESOS PARA PAGO DE GASTOS (Y4/Z4)
  = SUMIFS(BOLIVARES1; ORIGEN="CLIENTES"; DESTINO="GASTOS")     = 0

EGRESOS POR COMPRAS (Y8/Z8)
  = SUMIFS(BOLIVARES1; ORIGEN="BANCOS"; DESTINO="CLIENTES")     = 130700
  → "el negocio le compró bolívares/le pagó a un cliente desde un banco propio"

EGRESOS POR GASTOS (Y9/Z9)
  = SUM(NOMINA + CONTRATACION_SERVICIOS + OPERATIVOS + IMPUESTOS + PERDIDA + OTROS + REMODELACION)
  = 0  (en este período no hubo gastos operativos registrados)
```
**Regla para el API:** la "categoría" de un movimiento (venta, compra, gasto, comisión) **no
se captura**, se **deriva** de la combinación ORIGEN→DESTINO. El servicio de reportes debe
tener esta tabla de mapeo como reglas de negocio explícitas, ejemplo:

| ORIGEN | DESTINO | Categoría resultante |
|---|---|---|
| CLIENTES | BANCOS | Ingreso por ventas |
| CLIENTES | GASTOS | Ingreso para pago de gastos |
| BANCOS | CLIENTES | Egreso por compras |
| BANCOS | GASTOS | Egreso por gastos operativos |
| CLIENTES | CLIENTES | Movimiento interno (arbitraje, genera GANANCIA DIRECTA) |

### 2.6 Nómina y comisiones por empleado — proceso de "reparto"

Cada empleado tiene una fila fija que suma cuánto se le pagó, cruzando por su nombre contra el
catálogo `LISTAS!L3:L12`:

```
KAROL MALDONADO (Y25):
  Z25 (BS)  = SUMIF(RECEPTOR="KAROL MALDONADO", BOLIVARES2)         = 0
  AA25(USD) = SUMIF(Tabla12[RECEPTOR]="KAROL MALDONADO", MONTO2)     = 0
```
Es decir: se le paga a un empleado registrando un movimiento **normal** de BOLIVARES o DOLARES
donde el `RECEPTOR` es su nombre — el sistema NO tiene una tabla de nómina separada, sino que
"lee" los pagos de nómina de las mismas tablas transaccionales, filtrando por nombre.

**Implicación para el API:** o bien (a) se replica este patrón (todo pago se registra como
movimiento genérico y se clasifica por receptor), o (b) se diseña una tabla `pagos_nomina`
explícita — la segunda opción es más robusta y evita errores de digitación de nombres, pero
**rompe la trazabilidad 1:1 con el Excel**. Hay que decidir esto con el negocio.

### 2.7 Distribución de comisiones por socio — proceso de "reparto societario"

Este es el cálculo más particular del archivo. Cada socio (Karol, Sarah, Beatriz, Ana Karina)
tiene un **conjunto fijo de cuentas bancarias asignadas**, hardcodeado en la fórmula:

```
KAROL (Z42) = BANCOS!D3 + BANCOS!D9 + BANCOS!D15 + BANCOS!D18 + BANCOS!D20 + BANCOS!D23 + BANCOS!D25 + BANCOS!D27
             (suma el INGRESO de esas 8 cuentas bancarias específicas)
AB42 (excedente) = Z42 - AA42        (ingresos del socio menos su nómina en USD)
AC42 (comisión)  = AB42 * 0.003       (0.3% del excedente)
AD42 (nómina USD)= AB33               (nómina ya calculada en dólares)
AE42 (neto)      = AC42 - AD42
AF42 (en USD ref)= AC42 / 300         (conversión con tasa fija 300 — parámetro, no constante)
```

**Mapeo real banco → socio (hay que extraerlo a una tabla de configuración):**

| Socio | Bancos asignados (según fórmula) |
|---|---|
| KAROL MALDONADO | fila 3, 9, 15, 18, 20, 23, 25, 27 de la tabla BANCOS |
| SARAH ALVAREZ | fila 10, 11, 19, 26 |
| BEATRIZ TORREALBA | fila 5 |
| ANA KARINA MORENO | fila 21, 4 |

Esto **debe convertirse** en una tabla `socio_bancos(socio_id, banco_id)` editable, no quedar
fijo en fórmulas — si mañana cambia qué banco pertenece a qué socio, en Excel hay que reescribir
la fórmula; en el API solo se actualiza una fila de configuración.

---

## PROCESO 3 — Registrar una operación de cambio/canje (hoja `CAMBIOS`)

### 3.1 Qué captura el usuario

Ejemplo real, fila 3:

| Campo | Valor |
|---|---|
| FECHA | 15/07/2026 |
| CAMBIO | CASH/EUR |
| SOLICITANTES | SARAH ALVAREZ |
| RECIBIDO | 900 |
| ENVIAR | 1000 |
| COSTOS DE ENVÍO | (vacío = 0) |

Traducción: **"SARAH pidió cambiar; el negocio recibió 900 y tuvo que enviar 1000 (en las
monedas del par CASH/EUR)"**.

### 3.2 Cálculos automáticos

```
DIFERENCIA (H3)   = RECIBIDO - ENVIAR                          = 900 - 1000 = -100
PORCENTAJE (I3)   = DIFERENCIA * 100 / ENVIAR                   = -100*100/1000 = -10%
GANANCIA_REAL (J3)= DIFERENCIA - COSTOS_DE_ENVIO                = -100 - 0 = -100
% REAL (K3)       = (DIFERENCIA - COSTOS_DE_ENVIO)*100/ENVIAR   = -10%
```
**Interpretación de negocio:** en este ejemplo la operación dio **-100 USD de resultado** (una
pérdida del 10%), no una ganancia — el negocio recibió menos de lo que tuvo que entregar. Esto
alimenta directamente la columna `COMISION` del cliente SARAH en la hoja CLIENTES (ver Proceso
4), como un cargo negativo de -100.

### 3.3 Verificador de comisiones y costos de envío

```
COMISIONES_RECIBIDAS (O3) = SUM(toda la columna DIFERENCIA de CAMBIOS)         = -100
                     (P3)  = SUM(comisión ya reflejada en CLIENTES)             = -100
                     (R3)  = O3 - P3                                             = 0  ✅ Cuadra

COSTOS_DE_ENVIOS (O4) = SUM(toda la columna COSTOS DE ENVÍO)                    = 0
```

### 3.4 Seguimiento de TRON (sub-proceso específico de una plataforma cripto)

```
COMPRA_TRON (O9)      = SUMIF(Tabla12[RECEPTOR]="TRON", Tabla12[MONTO2])   = 0
DISPONIBLE_EN_TRON(O10)= COMPRA_TRON - SUM(COSTOS DE ENVÍO de CAMBIOS)      = 0
```
Este es un control aparte para saber cuánto USDT/cripto hay disponible en la wallet TRON,
alimentado por movimientos de la hoja DOLARES donde el receptor sea "TRON".

---

## PROCESO 4 — Consultar el estado de cuenta de un cliente (hoja `CLIENTES`)

Esta hoja **no se llena manualmente** (salvo `PENDIENTE`, que es el saldo inicial del período).
Todo lo demás se recalcula leyendo las tres hojas transaccionales. Ejemplo real — cliente
SARAH ALVAREZ:

```
PENDIENTE = (vacío, se asume 0 si no hay valor inicial)

COMPRA  = SUMIF(BOLIVARES[EMISOR]="SARAH ALVAREZ", BOLIVARES[DOLARES])         = 0
          (SARAH no vendió bolívares al negocio en este período)

VENTA   = SUMIF(BOLIVARES[RECEPTOR]="SARAH ALVAREZ", BOLIVARES[DOLARES2])      = 125
          (SARAH recibió el equivalente a 125 USD en la fila 7 de BOLIVARES)

PAGO    = SUMIF(Tabla12[EMISOR2]="SARAH ALVAREZ", Tabla12[MONTO])              = 900
          (SARAH envió 900 en la fila 4 de DOLARES)

RETIRO  = SUMIF(Tabla12[RECEPTOR]="SARAH ALVAREZ", Tabla12[MONTO2])            = 1000
          (SARAH recibió 1000 en la fila 3 de DOLARES)

COMISION= SUMIF(CAMBIOS[SOLICITANTE]="SARAH ALVAREZ", CAMBIOS[DIFERENCIA])
        + SUMIF(CAMBIOS[SOLICITANTE]="SARAH ALVAREZ", CAMBIOS[COSTOS DE ENVÍO])
        = -100 + 0 = -100

SALDO   = PENDIENTE + VENTA + RETIRO - COMPRA - PAGO + COMISION
        = 0 + 125 + 1000 - 0 - 900 + (-100)
        = 125
```
**El saldo de SARAH es 125.** Esto significa: sumando todo lo que la casa le debe (venta,
retiros a su favor) menos lo que ella ya recibió/entregó (compra, pagos) más/menos comisiones
de cambio, el negocio le debe actualmente 125 (en la unidad que corresponda, USD en este caso).

**Regla operativa:** este cálculo se dispara **cada vez que se toca cualquiera de las tres
hojas transaccionales** (BOLIVARES, DOLARES, CAMBIOS), no solo cuando se edita CLIENTES. En el
API esto se traduce en: el saldo de un cliente **nunca se guarda como campo editable**, siempre
se recalcula (o se invalida su caché) al insertar/editar/borrar un movimiento que lo involucre.

---

## PROCESO 5 — Consultar el estado de una cuenta bancaria (hoja `BANCOS`)

Ejemplo real — banco `BAN JOH`:

```
ACUMULADO (saldo inicial manual) = 92994.63

INGRESO = SUMIF(BOLIVARES[RECEPTOR]="BAN JOH", BOLIVARES[BOLIVARES1])           = 100000
          (fila 6: KAROL le entregó 100.000 Bs a este banco)

EGRESO  = SUMIF(BOLIVARES[EMISOR]="BAN JOH", BOLIVARES[BOLIVARES1])
        + SUMIF(BOLIVARES[EMISOR]="BAN JOH", BOLIVARES[COMISION])
        = 0 + 0 = 0

SALDO   = ACUMULADO + INGRESO - EGRESO = 92994.63 + 100000 - 0 = 192994.63

DISPONIBLE = (vacío — el usuario lo llenaría manualmente al conciliar con la banca en línea real)

DIFERENCIA = SALDO - DISPONIBLE = 192994.63 - 0 = 192994.63   (⚠️ como DISPONIBLE está vacío,
             la diferencia no es un error real, es solo que falta la conciliación manual)
```

**Fila de totales (fila 32):**
```
TOTAL_ACUMULADO = SUM(todos los ACUMULADO)  = 5271610.61
TOTAL_INGRESO    = SUM(todos los INGRESO)    = 100000
TOTAL_EGRESO     = SUM(todos los EGRESO)     = 130700
TOTAL_SALDO       = SUM(todos los SALDO)      = 5240910.61
```
Este `TOTAL_SALDO` es exactamente el mismo número que aparece como `ACUMULADO` /
`REMANENTE` en el Proceso 2.4 — es el patrimonio consolidado del negocio en bolívares.

---

## PROCESO 6 — Consultar el estado de una plataforma/wallet (hoja `PLATAFORMAS`)

Misma lógica que BANCOS, pero alimentada desde la tabla DOLARES en vez de BOLIVARES:

```
Ejemplo — CASH BELKIS:
ACUMULADO  = (vacío = 0, saldo inicial)
INGRESOS   = SUMIF(Tabla12[RECEPTOR]="CASH BELKIS", Tabla12[MONTO2])   = 0
EGRESOS    = SUMIF(Tabla12[EMISOR2]="CASH BELKIS", Tabla12[MONTO])     = 1000
             (la fila 3 de DOLARES: 1000 salieron de esta plataforma)
SALDO      = ACUMULADO + INGRESOS - EGRESOS = 0 + 0 - 1000 = -1000
```
> Nota: este saldo negativo (-1000) es información legítima — significa que la plataforma
> quedó "debiendo" 1000, probablemente porque no se registró el fondeo inicial de esa
> plataforma en este período. El API debería poder mostrar y alertar sobre saldos negativos
> en plataformas, ya que operacionalmente no deberían quedar en negativo sin explicación.

---

## PROCESO 7 — Secuencia completa de un "día de operación" (flujo end-to-end)

Uniendo todos los procesos anteriores, así es como se usa el archivo en la práctica, en orden:

```
1. Un cliente contacta pidiendo cambiar dinero (ej. tiene EUR, quiere USD)
   → Se evalúa si es:
      a) Operación simple de cambio de divisas fuera del circuito Bs → registrar en CAMBIOS
      b) Operación que involucra bolívares (venta/compra de Bs)      → registrar en BOLIVARES
      c) Simple transferencia de USD entre cuentas/clientes           → registrar en DOLARES

2. Se captura la fila correspondiente con FECHA, ORIGEN, EMISOR, MONTO, DESTINO, RECEPTOR
   (y TASA/TASA2 si aplica en BOLIVARES).

3. El Excel recalcula automáticamente (encadenado):
   a. Columnas derivadas de la misma fila (comisión, dólares equivalentes, ganancia directa)
   b. Los verificadores de la hoja (sumas cruzadas de control)
   c. El estado de cuenta del/los cliente(s) involucrados (hoja CLIENTES)
   d. El saldo del/los banco(s) involucrados (hoja BANCOS)
   e. El saldo de la/las plataforma(s) involucrada(s) (hoja PLATAFORMAS)
   f. Los reportes de distribución (ingresos, egresos, nómina, comisiones por socio)

4. Al final del día/período, se revisan los verificadores (columnas T-W en BOLIVARES,
   M-P en DOLARES, N-R en CAMBIOS) buscando que las diferencias ("DIF", columna P, W, R)
   den 0. Si no dan 0, hay un error de captura (origen/destino mal puesto, monto mal escrito,
   tasa faltante) que hay que corregir antes de cerrar el período.

5. Al cerrar el período (mensual, según el nombre del archivo), el SALDO final de cada
   banco/plataforma se convierte en el ACUMULADO (saldo inicial) del archivo del mes
   siguiente — es un snapshot manual, copiar-pegar de valores.
```

---

## PROCESO 8 — Tabla resumen: qué dispara qué (matriz de dependencias)

| Si insertas/editas una fila en... | Se recalculan automáticamente... |
|---|---|
| `DOLARES` | Verificadores de DOLARES · `PAGO`/`RETIRO` del cliente involucrado (hoja CLIENTES) · `INGRESOS`/`EGRESOS` de la plataforma involucrada (hoja PLATAFORMAS) · `SALDO` de ese cliente y esa plataforma |
| `BOLIVARES` | Comisión y ganancia directa de la fila · Verificadores de BOLIVARES (cuadres, remanente, acumulado) · `COMPRA`/`VENTA` del cliente involucrado (hoja CLIENTES) · `INGRESO`/`EGRESO` del banco involucrado (hoja BANCOS) · Distribución de ingresos/egresos/nómina/comisiones por socio · `SALDO` de ese cliente y ese banco |
| `CAMBIOS` | Diferencia, porcentaje y ganancia real de la fila · Verificador de comisiones recibidas · `COMISION` del cliente solicitante (hoja CLIENTES) · `SALDO` de ese cliente |
| `PENDIENTE` (manual, en CLIENTES) | `SALDO` de ese cliente únicamente |
| `ACUMULADO` (manual, en BANCOS/PLATAFORMAS) | `SALDO` de ese banco/plataforma · Totales globales · `REMANENTE`/`ACUMULADO` global en BOLIVARES |
| `DISPONIBLE` (manual, en BANCOS/PLATAFORMAS) | `DIFERENCIA` de ese banco/plataforma (alerta de cuadre) |

**Esta matriz es, en esencia, el "grafo de dependencias" que el API debe implementar** —
idealmente como triggers/eventos después de cada escritura, o como campos calculados on-demand
en cada consulta (recomendado para evitar inconsistencias de caché).

---

## Notas finales sobre exactitud

- Todos los números de este documento son los **valores reales calculados por Excel** en el
  archivo proporcionado (no inventados), obtenidos leyendo la caché de fórmulas del `.xlsx`.
- Donde el archivo tiene un descuadre real (ej. Proceso 1.3, verificador de plataformas = 1000
  en vez de 0), se dejó explícito porque **el API debe reproducir la misma lógica de cálculo**,
  no "corregir" silenciosamente el comportamiento — la corrección de datos es un tema aparte
  de la corrección del diseño del sistema.
- Los signos de ganancia/pérdida (ej. Proceso 2.3, 3.2) dependen de la convención que use el
  negocio; se recomienda confirmarlos con el dueño del proceso antes de fijarlos como reglas
  inmutables en el backend.
