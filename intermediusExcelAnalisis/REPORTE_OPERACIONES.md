# Reporte Detallado de Operaciones - Excel Intermedius

**Archivo:** `IG.ENERO.KMSA20.01 - NUEVO (3) PRUEBA VICTOR.xlsx`

---

## Resumen General

Este archivo Excel es un **sistema contable de una casa de cambio/intermediario financiero** que gestiona operaciones de compra-venta de divisas (dólares, euros, bolívares, USDT, etc.) entre clientes, bancos y plataformas de pago. Está compuesto por **7 hojas funcionales** más 1 hoja vacía.

---

## Hojas del Libro

| Hoja | Función |
|------|---------|
| **DOLARES** | Registro de transacciones en dólares/euros entre plataformas y clientes |
| **BOLIVARES** | Registro de transacciones en bolívares, con verificadores de ganancia y distribución de ingresos/egresos |
| **CAMBIOS** | Registro de solicitudes de cambio de divisa por parte de clientes |
| **CLIENTES** | Dashboard de saldos y movimientos por cliente (calculado automáticamente) |
| **BANCOS** | Control de saldos por cuenta bancaria (calculado automáticamente) |
| **PLATAFORMAS** | Control de saldos por plataforma de pago (calculado automáticamente) |
| **LISTAS** | Listas de referencia/desplegables usadas en validaciones y fórmulas |
| **Hoja1** | Vacía |

---

## 1. Hoja DOLARES - Registro de Transacciones en Dólares

### Propósito
Registra movimientos de dinero en **dólares y euros** entre las cuentas de la empresa (plataformas) y los clientes.

### Columnas Principales

| Columna | Campo | Descripción |
|---------|-------|-------------|
| B | FECHA | Fecha de la transacción |
| C | ORIGEN2 | Entidad origen: puede ser `PLATAFORMAS`, `CLIENTES`, `BANCOS` |
| D | EMISOR2 | Nombre de quien envía (ej: CASH BELKIS, SARAH ALVAREZ) |
| E | MONEDA | Tipo de moneda: `CASH` (USD físico), `EUR`, `PANAMA`, `ZINLI`, `BOFA`, `ZELLE`, etc. |
| F | MONTO | Monto enviado |
| G | DESTINO | Entidad destino: `CLIENTES`, `PLATAFORMAS`, `BANCOS` |
| H | RECEPTOR | Nombre de quien recibe |
| I | MONTO2 | Monto recibido (= MONTO de Tabla12, es decir, el mismo monto de la tabla DOLARES) |
| J | REMITENTE | Campo adicional (no usado en fórmulas visibles) |
| K | RECEPTOR2 | Campo adicional |

### Verificador de USD (Columnas M-P)

Sección de validación que cruza datos entre DOLARES y PLATAFORMAS:

| Fórmula | Descripción |
|---------|-------------|
| **USD RECIBIDO** (N3) | `=SUMIF(Tabla12[ORIGEN2], LISTAS!B4, Tabla12[MONTO])` - Suma lo que los clientes enviaron a la empresa |
| **USD PAGADO** (N4) | `=SUMIFS(Tabla12[MONTO], Tabla12[ORIGEN2], LISTAS!B5, Tabla12[DESTINO], ...)` - Suma lo que la empresa pagó a clientes |
| **USD PAGADO CLIENTES** (N5) | `=SUMIFS(...)` - Pagos entre clientes |
| **INGRESOS** (N9) | `=SUMIF(Tabla12[DESTINO], LISTAS!B5, Tabla12[MONTO2])` - Total ingresos a PLATAFORMAS |
| **EGRESOS** (N10) | `=SUMIF(Tabla12[ORIGEN2], LISTAS!B5, Tabla12[MONTO])` - Total egresos desde PLATAFORMAS |

### Verificador de PLATAFORMAS (Columnas M-P, filas 8-10)
Cruza los datos de la hoja DOLARES contra los totales de la hoja PLATAFORMAS para verificar concordancia.

### Ejemplo de Flujo
```
CASH BELKIS (Plataforma) → envía $1000 → SARAH ALVAREZ (Cliente)
SARAH ALVAREZ (Cliente) → envía €900 → EVELIO RAMIREZ (Cliente)
```

---

## 2. Hoja BOLIVARES - Registro de Transacciones en Bolívares

### Propósito
Es la hoja **más compleja** del libro. Registra todas las operaciones en bolívares (VES) y contiene múltiples secciones de verificación, cálculo de ganancias y distribución de ingresos/egresos.

### Columnas Principales (A-P)

| Columna | Campo | Descripción |
|---------|-------|-------------|
| B | FECHA | Fecha de la transacción |
| C | ORIGEN | Entidad origen: `BANCOS`, `CLIENTES`, `PLATAFORMAS` |
| D | EMISOR | Nombre de quien envía (persona o banco) |
| E | MONEDA | Siempre `BS` (bolívares) |
| F | DOLARES | Equivalente en dólares (cuando aplica) |
| G | TASA | Tipo de cambio aplicado |
| H | BOLIVARES1 | Monto en bolívares enviado |
| I | COMISION | Comisión cobrada = `BOLIVARES1 * 0.003` (0.3%) |
| J | REF | Número de referencia del pago móvil |
| K | DESTINO | Entidad destino |
| L | RECEPTOR | Nombre de quien recibe |
| M | MONEDA2 | Moneda en que recibe: `CASH`, `PANAMA`, etc. |
| N | DOLARES2 | Equivalente en dólares recibido = `BOLIVARES2 / TASA2` |
| O | TASA2 | Tipo de cambio de conversión |
| P | BOLIVARES2 | Monto en bolívares recibido (igual al enviado si es misma moneda) |
| Q | DESCRIPCIÓN | Texto descriptivo de la operación |
| R | GANANCIA DIRECTA | Ganancia directa de la operación |

### Verificador de USD x Bolívares (Columnas S-V)

Sección de verificación que analiza la conversión entre dólares y bolívares:

| Fórmula | Descripción |
|---------|-------------|
| **VENDIDO** (U3) | Monto de bolívares vendidos a clientes por USD |
| **COMPRADO** (U4) | Monto de bolívares comprados a clientes por USD |
| **RECIBIDOS EN BANCOS** (U6) | `=SUMIF(...)` - Bolívares recibidos en cuentas bancarias |
| **ENVIADO DESDE BANCOS** (U7) | `=SUMIF(...)` - Bolívares enviados desde bancos |
| **TOTAL INGRESOS** (U13) | `=SUMIF(BOLIVARES[DESTINO], "BANCOS", BOLIVARES[BOLIVARES1])` |
| **TOTAL EGRESOS** (U14) | `=SUMIF(BOLIVARES[ORIGEN], "BANCOS", BOLIVARES[BOLIVARES1])` |
| **REMANENTE** (U17) | `=(U14+U19) - U15 - U21 - U20` - Lo que sobra después de egresos |
| **ACUMULADO** (U19) | `=Tabla16[[#Totals],[ACUMULADO]]` - Total acumulado de bancos |
| **COMPRAR** (U32) | `=U31 / U46` - Cuántos dólares se pueden comprar con el remanente |
| **GANANCIA** (U33) | `=U32 + U29` - Ganancia total |

### Verificador de Bolívares Totales (Columnas T-V)

Resumen de totales para verificación cruzada.

### Verificador de Bolívares en Bancos (Columnas T-W)

| Fórmula | Descripción |
|---------|-------------|
| **VENTA A CUENTAS** (U26) | `=SUMIFS(BOLIVARES[BOLIVARES1], BOLIVARES[ORIGEN], "CLIENTES", BOLIVARES[DESTINO], "BANCOS")` |
| **DOLARES VENDIDOS PARA TRABAJAR** (U27) | Dólares vendidos por clientes que se usan para operar |
| **USD COMPRADOS** (U28) | Dólares comprados de bancos hacia clientes |
| **BS USADOS** (U30) | Bolívares usados para comprar dólares |
| **PROMEDIO DE COMPRAS A CLIENTES** (U36) | `=SUMIFS(BOLIVARES[BOLIVARES1], ORIGEN, "BANCOS", DESTINO, "CLIENTES")` |
| **DOLARES COMPRADOS DE BANCOS** (U37) | Valor fijo: `64182` |

### Distribución de Ingresos (Columnas T-Z, filas 13-19)

| Concepto | Fórmula |
|----------|---------|
| **INGRESOS POR VENTAS** | `=SUMIF(BOLIVARES[DESTINO], "CLIENTES", BOLIVARES[BOLIVARES2])` |
| **INGRESOS PARA PAGO DE GASTOS** | Suma de ingresos destinados a gastos |
| **INGRESOS POR PAGO DE INTERESES** | Ingresos por intereses |
| **TOTAL INGRESOS** | Suma de todos los ingresos |
| **TOTAL EGRESOS** | Suma de todos los egresos |
| **REMANENTE** | `(INGRESOS + ACUMULADO) - EGRESOS - GASTOS - COMISIONES` |
| **ACUMULADO** | Saldo acumulado total |

### Distribución de Egresos (Columnas Y-Z)

| Concepto | Fórmula |
|----------|---------|
| **EGRESOS POR COMPRAS** | Bolívares gastados en compras |
| **EGRESOS POR GASTOS** | Bolívares gastados en gastos operativos |
| **COMISIONES POR PAGO MOVIL** | Comisiones por transferencias |
| **CONTRATACION DE SERVICIOS** | Pago de servicios contratados |

### Distribución de la Nómina (Columnas Y-AA)

Calcula la nómina pagada a cada persona usando `SUMIF` contra la hoja BOLIVARES:

- **KAROL MALDONADO**: `=SUMIF(BOLIVARES[RECEPTOR], "KAROL MALDONADO", BOLIVARES[BOLIVARES2])`
- **MIGUEL RAMONES**: `=SUMIF(...)`
- **SARAH ALVAREZ**: `=SUMIF(...)`
- **SERGIO LOPEZ**: `=SUMIF(...)`
- **YENSI RIOS**: `=SUMIF(...)`
- **BEATRIZ TORREALBA**: `=SUMIF(...)`
- **ANA KARINA MORENO**: `=SUMIF(...)`

### Distribución de las Comisiones (Columnas Y-AB)

Calcula comisiones individuales y alquileres:

| Persona | Fórmula BS | Fórmula USD | ALQUILER |
|---------|-----------|-------------|----------|
| EDUARD OCHOA | `=SUMIF(BOLIVARES[RECEPTOR], "EDUARD OCHOA", BOLIVARES2])` | `=SUMIF(Tabla12[RECEPTOR], "EDUARD OCHOA", Tabla12[MONTO2])` | `=SUMIF(BOLIVARES[RECEPTOR], "EDUARD OCHOA", BOLIVARES2)` (alquiler) |
| KAROL MALDONADO | ... | ... | ... |
| SARAH ALVAREZ | ... | ... | ... |
| YENSI RIOS | ... | ... | ... |
| BEATRIZ TORREALBA | ... | ... | ... |

### Distribución Individual de Bancos por Persona (Filas 42-45)

Calcula el monto total que cada persona tiene en bancos usando fórmulas directas:

| Persona | Bancos |
|---------|--------|
| **KAROL MALDONADO** | `BANCOS!D3 + D9 + D15 + D18 + D20 + D23 + D25 + D27` |
| **SARAH ALVAREZ** | `BANCOS!D10 + D11 + D19 + D26` |
| **BEATRIZ TORREALBA** | `BANCOS!D5` |
| **ANA KARINA MORENO** | `BANCOS!D21 + D4` |

Luego calcula:
- **Comisión** = `(Total Bancos - Nómina Pagada) * 0.003`
- **Comisión Neta** = `Comisión - Costo de nómina en USD`
- **Ganancia** = `Comisión Neta - Costo nómina`

---

## 3. Hoja CAMBIOS - Registro de Cambios de Divisa

### Propósito
Registra las solicitudes de cambio de divisa realizadas por clientes (ej: cambiar EUR a USD).

### Columnas Principales

| Columna | Campo | Descripción |
|---------|-------|-------------|
| B | FECHA | Fecha de la solicitud |
| C | CAMBIO | Par de divisas: `CASH/EUR`, `USD/EUR`, `PY/CASH`, `CASH/PY`, `USDT/CASH`, `CASH/USDT`, `ZELLE/CASH`, etc. |
| D | SOLICITANTES | Nombre del cliente que solicita |
| E | RECIBIDO | Monto de divisa recibido por la empresa |
| F | ENVIAR | Monto de divisa a enviar al cliente |
| G | COSTOS DE ENVÍO | Costos asociados al envío |
| H | DIFERENCIA | `=RECIBIDO - ENVIAR` |
| I | PORCENTAJE | `=DIFERENCIA * 100 / ENVIAR` (margen en %) |
| J | GANANCIA REAL | `=DIFERENCIA - COSTOS DE ENVÍO` |
| K | % REAL | `=(DIFERENCIA - COSTOS DE ENVÍO) * 100 / ENVIAR` |

### Verificador de USD (Columnas N-R)

| Concepto | Fórmula |
|----------|---------|
| **COMISIONES RECIBIDAS** | `=SUM(CAMBIOS[DIFERENCIA])` - Total de diferencias cobradas |
| **COSTOS DE ENVIOS** | `=SUM(CAMBIOS[COSTOS DE ENVÍO])` |
| **Ganancia neta** | `=COMISIONES RECIBIDAS - COSTOS DE ENVIOS` |
| **COMPRA TRON** | `=SUMIF(Tabla12[RECEPTOR], "TRON", Tabla12[MONTO2])` |
| **DISPONIBLE EN TRON** | `=COMPRA TRON - COSTOS DE ENVIOS` |

### Ejemplo
```
SARAH ALVAREZ solicita cambiar EUR/USD
Recibe: 900 EUR → Empresa envía: 1000 USD
Diferencia: -100 USD (pérdida)
Porcentaje: -10%
```

---

## 4. Hoja CLIENTES - Dashboard de Clientes

### Propósito
Vista consolidada de todos los movimientos de cada cliente, calculada automáticamente desde las hojas DOLARES, BOLIVARES y CAMBIOS.

### Columnas y Fórmulas

| Columna | Campo | Fórmula |
|---------|-------|---------|
| B | CLIENTE | Nombre del cliente (desde Tabla3) |
| C | PENDIENTE | Saldo pendiente del cliente (valor manual o previo) |
| D | COMPRA | `=SUMIF(BOLIVARES[EMISOR], [CLIENTE], BOLIVARES[DOLARES])` - Cuánto dólares le compraron al cliente |
| E | VENTA | `=SUMIF(BOLIVARES[RECEPTOR], [CLIENTE], BOLIVARES[DOLARES2])` - Cuánto dólares le vendieron al cliente |
| F | PAGO | `=SUMIF(Tabla12[EMISOR2], [CLIENTE], Tabla12[MONTO])` - Pagos enviados por el cliente |
| G | RETIRO | `=SUMIF(Tabla12[RECEPTOR], [CLIENTE], Tabla12[MONTO2])` - Retiros recibidos por el cliente |
| H | COMISION | `=SUMIF(CAMBIOS[SOLICITANTES], [CLIENTE], CAMBIOS[DIFERENCIA])` - Comisiones generadas por cambios |
| I | SALDO | `=PENDIENTE + VENTA + COMPRA + PAGO + RETIRO + COMISION` |
| J | TIPO | Tipo de negocio del cliente (MOTO, TINTORERIA, PANAMA, EUROS, etc.) |
| K | GRUPO | Grupo al que pertenece (GRUPO A, etc.) |

### Clientes Registrados
- EVELIO RAMIREZ (MOTO)
- JOHANA RODRIGUEZ (TINTORERIA)
- KAROL MALDONADO (PANAMA)
- SARAH ALVAREZ (EUROS)
- + 110 filas adicionales (la mayoría vacías/preparadas para datos)

### Lógica del Saldo
El saldo del cliente se calcula como:
```
SALDO = PENDIENTE + COMPRA + VENTA + PAGO + RETIRO + COMISION
```
Donde:
- **COMPRA**: Lo que la empresa le compró al cliente (entrada de dinero al cliente)
- **VENTA**: Lo que la empresa le vendió al cliente (salida de dinero del cliente)
- **PAGO**: Pagos que el cliente hizo a la empresa
- **RETIRO**: Retiros que el cliente hizo de la empresa
- **COMISION**: Comisiones generadas por cambios de divisa

---

## 5. Hoja BANCOS - Control de Cuentas Bancarias

### Propósito
Dashboard que muestra el saldo de cada cuenta bancaria de la empresa y sus movimientos.

### Columnas y Fórmulas

| Columna | Campo | Fórmula |
|---------|-------|---------|
| B | BANCOS | Nombre de la cuenta bancaria (desde Tabla4) |
| C | ACUMULADO | Saldo acumulado inicial (valor fijo manual) |
| D | INGRESO | `=SUMIF(BOLIVARES[RECEPTOR], [BANCO], BOLIVARES[BOLIVARES1])` |
| E | EGRESO | `=SUMIF(BOLIVARES[EMISOR], [BANCO], BOLIVARES[BOLIVARES1]) + ...` |
| F | SALDO | `=ACUMULADO + INGRESO - EGRESO` |
| G | DISPONIBLE | Saldo disponible (ingresado manualmente solo para cuentas principales) |
| H | DIFERENCIA | `=SALDO - DISPONIBLE` (detecta discrepancias) |
| I | OBSERVACION | Notas |

### Cuentas Bancarias Registradas (30 cuentas)

| Cuenta | Acumulado | Banco |
|--------|-----------|-------|
| BAN ALE | - | Banco de Alejandra |
| BAN ANA K | 600,000 | Banco de Ana Karina |
| BAN BEA | 422,000 | Banco de Beatriz |
| BAN BEL | - | Banco de Belkis |
| BAN EVE | 20,409.05 | Banco de Evelio |
| BAN JOH | 92,994.63 | Banco de Johana |
| BAN KAR | - | Banco de Karol |
| BAN SAR | 6,900 | Banco de Sarah |
| MER SAR | - | Mercantil de Sarah |
| MERC BEL | 22,790.29 | Mercantil de Belkis |
| MERC EVE | 142.73 | Mercantil de Evelio |
| MERC JOH | 301,640.47 | Mercantil de Johana |
| MERC KAR | 430,600.64 | Mercantil de Karol |
| PROV EVE | 4,694.29 | Provincial de Evelio |
| PROV SAR | 202,327.60 | Provincial de Sarah |
| VZLA ALE | 59,475 | Venezuela de Alejandra |
| VZLA EVE | 562.25 | Venezuela de Evelio |
| VZLA JOHA | 1,806,032.88 | Venezuela de Johana |
| VZLA KAR | 11,888.11 | Venezuela de Karol |
| VZLA BEL | 5,054.19 | Venezuela de Belkis |
| BANCA KAR | 648,261.16 | Banca de Karol |
| BANCA EVE | 370,415.76 | Banca de Evelio |
| BANCA MANUEL | 265,000 | Banca de Manuel |
| BANCARIBE EVE | 421.56 | BANCARIBE de Evelio |

### Totales
| Concepto | Valor |
|----------|-------|
| **Total ACUMULADO** | 5,271,610.61 |
| **Total INGRESOS** | 100,000 |
| **Total EGRESOS** | 130,700 |
| **Total SALDO** | 5,240,910.61 |

---

## 6. Hoja PLATAFORMAS - Control de Plataformas de Pago

### Propósito
Dashboard que muestra el saldo de cada plataforma de pago utilizada (billeteras electrónicas, apps de pago, etc.).

### Columnas y Fórmulas

| Columna | Campo | Fórmula |
|---------|-------|---------|
| B | PLATAFORMAS | Nombre de la plataforma (desde Tabla10) |
| C | ACUMULADO | Saldo acumulado (valor fijo manual, si aplica) |
| D | INGRESOS | `=SUMIF(Tabla12[RECEPTOR], [PLATAFORMA], Tabla12[MONTO2])` |
| E | EGRESOS | `=SUMIF(Tabla12[EMISOR2], [PLATAFORMA], Tabla12[MONTO])` |
| F | SALDO | `=ACUMULADO + INGRESOS - EGRESOS` |
| G | DISPONIBLE | Saldo disponible real |
| H | DIFERENCIA | `=SALDO - DISPONIBLE` |

### Plataformas Registradas (29 plataformas)

| Plataformas | Tipo |
|-------------|------|
| CASH SARAH | Efectivo USD |
| CASH TINTORERIA | Efectivo |
| CASH YENSY | Efectivo |
| CASH GUILLERMO | Efectivo |
| ZELLE ITSIA | Zelle |
| EUROS CASH SARAH | Efectivo EUR |
| BNB SARAH | Binance Pay |
| BANPA KAROL | BanPay |
| MERCAPY KAROL | Mercado Pago |
| BNB KAROL | Binance Pay |
| ZINLI KAROL | Zinli |
| BANPA EVE | BanPay |
| MERCAPY EVE | Mercado Pago |
| BNB EVE | Binance Pay |
| ZINLI EVE | Zinli |
| TRUST WALLET I-IV | Trust Wallet (crypto) |
| TRUST WALLET ETH | Trust Wallet Ethereum |
| BANPA BELKIS | BanPay |
| CASH BELKIS | Efectivo |
| EUR BELKIS | Efectivo EUR |
| BANPA JOHA | BanPay |
| BNB JOHA | Binance Pay |
| ZINLI JOHA | Zinli |
| TRUIST BANK | Truist Bank (USA) |
| BANCO 53 | Banco USA |
| BOFA IG | Bank of America |

### Movimiento Registrado
- **CASH BELKIS**: EGRESOS = 1,000 (envío a SARAH ALVAREZ en DOLARES)

---

## 7. Hoja LISTAS - Listas de Referencia

### Propósito
Contiene todas las listas de valores que se usan como referencias en las fórmulas de validación y cálculo de las demás hojas.

### Listas

#### ORIGEN (Columna B)
```
BANCOS, CLIENTES, PLATAFORMAS
```

#### CLIENTES (Columna D)
```
EVELIO RAMIREZ, JOHANA RODRIGUEZ, KAROL MALDONADO, SARAH ALVAREZ
```

#### BANCOS (Columna F)
```
BAN ALE, BAN ANA K, BAN BEA, BAN BEL, BAN EVE, BAN JOH, BAN KAR, BAN SAR,
MER SAR, MERC BEL, MERC EVE, MERC JOH, MERC KAR, PROV EVE, PROV JOH,
PROV KAR, PROV SAR, VZLA ALE, VZLA ANA K, VZLA EVE, VZLA JHAS, VZLA JOHA,
VZLA KAR, VZLA SAR, VZLA BEL, BANCA KAR, BANCA EVE, BANCA MANUEL, BANCARIBE EVE
```

#### MONEDAS (Columna H)
```
BS, CASH, PANAMA, ZINLI, EUROS, USDT, BOFA, ZELLE, FACEBANK
```

#### DESTINO (Columna J)
```
BANCOS, CLIENTES, GASTOS, PLATAFORMAS
```

#### GASTOS (Columna L)
```
AHORRO, ALQUILER KAROL, ALQUILER SARAH, COMISION KAROL, COMISION SARAH,
NOMINA KAROL, NOMINA SARAH, NOMINA SERGIO, NOMINA YENSI, OPERATIVOS,
OTROS, PERDIDAS, TRX, (alquileres individuales), (otras personas)
```

#### PLATAFORMAS (Columna N)
```
CASH SARAH, CASH TINTORERIA, CASH YENSY, CASH GUILLERMO, ZELLE ITSIA,
EUROS CASH SARAH, BNB SARAH, BANPA KAROL, MERCAPY KAROL, BNB KAROL,
ZINLI KAROL, BANPA EVE, MERCAPY EVE, BNB EVE, ZINLI EVE,
TRUST WALLET I-IV, TRUST WALLET ETH, BANPA BELKIS, CASH BELKIS,
EUR BELKIS, BANPA JOHA, BNB JOHA, ZINLI JOHA, TRUIST BANK, BANCO 53, BOFA IG
```

#### CAMBIOS (Columna P)
```
EUR/USD, USD/EUR, PY/CASH, CASH/PY, USDT/CASH, CASH/USDT,
ZELLE/CASH, USDT/EUR, USDT/ZELLE, EUR/ZELLE, PRESTAMO, ZINLI/CASH, EUR/CASH
```

---

## Tablas Internas Referenciadas en Fórmulas

Las fórmulas hacen referencia a tablas con nombres `Tabla3`, `Tabla4`, `Tabla10`, `Tabla12`, `Tabla14`, `Tabla16`, `Tabla17` que corresponden a rangos de datos definidos dentro de las hojas:

| Tabla | Hoja | Contenido |
|-------|------|-----------|
| **Tabla3** | CLIENTES | Lista de nombres de clientes |
| **Tabla4** | BANCOS | Lista de nombres de bancos |
| **Tabla10** | PLATAFORMAS | Lista de nombres de plataformas |
| **Tabla12** | DOLARES | Datos de transacciones DOLARES |
| **Tabla14** | CLIENTES | Datos completos de clientes (pendiente, compra, venta, etc.) |
| **Tabla16** | BANCOS | Datos completos de bancos (acumulado, ingreso, egreso, saldo) |
| **Tabla17** | PLATAFORMAS | Datos completos de plataformas (acumulado, ingresos, egresos, saldo) |

---

## Flujo General de Operaciones

```
                    ┌─────────────────────────────────────┐
                    │         LISTAS (REFERENCIAS)         │
                    └─────────────────────────────────────┘
                                    │
              ┌─────────────────────┼─────────────────────┐
              │                     │                     │
              ▼                     ▼                     ▼
     ┌────────────────┐   ┌─────────────────┐   ┌─────────────────┐
     │  DOLARES        │   │  BOLIVARES      │   │  CAMBIOS        │
     │  (USD/EUR)      │   │  (VES)          │   │  (Divisas)      │
     └────────────────┘   └─────────────────┘   └─────────────────┘
              │                     │                     │
              └─────────────────────┼─────────────────────┘
                                    │
              ┌─────────────────────┼─────────────────────┐
              │                     │                     │
              ▼                     ▼                     ▼
     ┌────────────────┐   ┌─────────────────┐   ┌─────────────────┐
     │  CLIENTES      │   │  BANCOS         │   │  PLATAFORMAS    │
     │  (Dashboard)   │   │  (Dashboard)    │   │  (Dashboard)    │
     └────────────────┘   └─────────────────┘   └─────────────────┘
```

### Tipos de Operación

1. **Compra de dólares a clientes** (BOLIVARES → CLIENTES, DESTINO=CLIENTES)
   - La empresa recibe bolívares y entrega dólares
   - Se registra en BOLIVARES con ORIGEN=CLIENTES, DESTINO=BANCOS

2. **Venta de dólares a clientes** (PLATAFORMAS → CLIENTES)
   - La empresa entrega dólares desde sus plataformas
   - Se registra en DOLARES

3. **Transferencia entre clientes** (CLIENTE → CLIENTE)
   - Un cliente envía dinero a otro cliente
   - Se registra en DOLARES con ORIGEN=CLIENTES, DESTINO=CLIENTES

4. **Cambio de divisa** (CAMBIOS)
   - Cliente solicita convertir una moneda a otra
   - Se registra en CAMBIOS con el par de divisas

5. **Pago de nómina** 
   - Se calcula automáticamente en BOLIVARES con SUMIF

6. **Pago de gastos operativos**
   - Alquileres, comisiones, contratación de servicios
   - Se distribuye automáticamente

### Comisiones

- **Comisión por transacción en bolívares**: 0.3% (`=BOLIVARES1 * 0.003`)
- **Comisiones por cambio de divisa**: Diferencia entre RECIBIDO y ENVIAR en CAMBIOS

---

## Fórmulas Clave Resumidas

| Concepto | Fórmula |
|----------|---------|
| Comisión BS | `=BOLIVARES1 * 0.003` |
| Dólares recibidos en BS | `=BOLIVARES2 / TASA2` |
| Saldo Cliente | `=PENDIENTE + COMPRA + VENTA + PAGO + RETIRO + COMISION` |
| Saldo Banco | `=ACUMULADO + INGRESO - EGRESO` |
| Diferencia Banco | `=SALDO - DISPONIBLE` |
| Ganancia Cambio | `=DIFERENCIA - COSTOS DE ENVÍO` |
| % Ganancia | `=DIFERENCIA * 100 / ENVIAR` |
| Remanente BS | `=(INGRESOS + ACUMULADO) - EGRESOS - GASTOS - COMISIONES` |
| USD a Comprar | `=REMANENTE / TASA` |
