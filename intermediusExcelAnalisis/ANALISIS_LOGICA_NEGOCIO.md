# Analisis Detallado de Logica de Negocio - Intermedius Excel

**Archivo:** `IG.ENERO.KMSA20.01 - NUEVO (3) PRUEBA VICTOR.xlsx`

---

## 1. Naturaleza del Negocio

Este archivo es el **libro contable central** de una operacion de **casa de cambio / intermediario financiero** que opera en Venezuela. La empresa actua como puente entre:

- **Personas que tienen dolares/euros** (fisicos o en plataformas) y quieren venderlos
- **Personas que necesitan dolares/euros** y quieren comprarlos con bolivares
- **Personas que necesitan hacer pagos** en diferentes monedas o plataformas

La empresa **no es solo un exchange**. Tambien:
- Mantiene cuentas bancarias personales de sus operadores/empleados
- Realiza pagos moviles en bolivares por encargo de clientes
- Presta servicios de transferencia internacional
- Cobra comisiones por cada operacion (0.3% en bolivares)
- Realiza cambios de divisa con margen propio

---

## 2. Los Actores del Sistema

### 2.1 La Empresa (Intermedius)
No aparece como entidad explicita. Opera a traves de:
- Sus **plataformas de cobro** (CASH, Zelle, Binance, Zinli, etc.)
- Las **cuentas bancarias** de sus operadores
- Su caja fisica (CASH)

### 2.2 Clientes Registrados (4 personas activas)

| Cliente | Tipo de Negocio | Rol |
|---------|----------------|-----|
| **EVELIO RAMIREZ** | MOTO (motocicleta/delivery) | Cliente que compra/vende dolares |
| **JOHANA RODRIGUEZ** | TINTORERIA | Cliente con saldo pendiente |
| **KAROL MALDONADO** | PANAMA | Cliente + operadora (tiene cuentas bancarias propias) |
| **SARAH ALVAREZ** | EUROS | Cliente + operadora (maneja euros) |

### 2.3 Operadores / Empleados
Personas que manejan cuentas bancarias de la empresa y reciben nomina:

| Operador | Cuentas Bancarias Asignadas |
|----------|---------------------------|
| **KAROL MALDONADO** | BAN JOH, MERC KAR, PROV KAR, VZLA KAR, BANCA KAR, VZLA JHAS, VZLA ANA K, VZLA BEL |
| **SARAH ALVAREZ** | BAN SAR, MER SAR, PROV SAR, VZLA SAR |
| **BEATRIZ TORREALBA** | BAN BEA |
| **ANA KARINA MORENO** | VZLA ALE, BAN ANA K |

### 2.4 Otros Nombres en el Sistema
- **MIGUEL RAMONES** - Recibe pagos de nomina
- **SERGIO LOPEZ** - Recibe pagos de nomina
- **YENSI RIOS** - Recibe pagos de nomina
- **EDUARD OCHOA** - Recibe comisiones por alquiler
- **EVELIO RAMIREZ** - Tambien aparece como receptor en transacciones

---

## 3. Las Tres Monedas Principales

### 3.1 DOLARES (USD)
Operados en la hoja **DOLARES**. Incluyen:
- **CASH**: Dolares fisicos en efectivo
- **EUR**: Euros (tratados como moneda dentro del sistema USD)
- **ZELLE**: Dolares electronicos via Zelle
- **BOFA**: Dolares en Bank of America
- **FACEBANK**: Dolares en Facebank
- **ZINLI**: Dolares via Zinli (billetera digital)
- **PANAMA**: Dolares en cuentas panamenas
- **USDT**: Stablecoin (criptomoneda atada al dolar)
- **TRON/TRUST WALLET**: Criptomonedas

### 3.2 BOLIVARES (VES / BS)
Operados en la hoja **BOLIVARES**. Siempre en moneda `BS`. Las operaciones en bolivares incluyen:
- Pagos moviles (transferencias bancarias instantaneas en Venezuela)
- Referencias de pago (columna REF)
- Tipos de cambio variables (columnas TASA y TASA2)

### 3.3 CAMBIOS (Divisas Varias)
Operados en la hoja **CAMBIOS**. Son conversiones entre pares de monedas:
- EUR/USD, USD/EUR
- PY/CASH (PayPal a efectivo)
- CASH/PY (Efectivo a PayPal)
- USDT/CASH, CASH/USDT
- ZELLE/CASH
- USDT/EUR, USDT/ZELLE, EUR/ZELLE
- PRESTAMO
- ZINLI/CASH, EUR/CASH

---

## 4. Flujo de Dinero Detallado por Hoja

### 4.1 Hoja DOLARES - El Corazon de las Operaciones en Divisa

Cada fila representa **una transferencia** de dinero de un punto A a un punto B.

**Estructura del flujo:**
```
ORIGEN (quien envia) → DESTINO (quien recibe)
```

**Los 6 tipos de flujo posibles:**

#### Tipo 1: PLATAFORMA → CLIENTE (Venta de dolares)
```
Fila 3: CASH BELKIS → SARAH ALVAREZ = $1,000 USD
```
- La empresa entrega dolares fisicos desde su caja (CASH BELKIS) a un cliente
- El monto en MONTO2 se calcula como `=Tabla12[[#This Row],[MONTO]]` = mismo monto
- **Significado**: La empresa le vendio $1,000 a Sarah

#### Tipo 2: CLIENTE → CLIENTE (Transferencia entre clientes)
```
Fila 4: SARAH ALVAREZ → EVELIO RAMIREZ = €900 EUR
```
- Sarah transfiere euros a Evelio a traves de la empresa
- La empresa actua como intermediario
- **Significado**: Sarah pago euros que Evelio recibio

#### Tipo 3: CLIENTE → BANCOS (Deposito a cuenta bancaria)
- Un cliente deposita bolivares a una cuenta bancaria de la empresa

#### Tipo 4: BANCOS → CLIENTE (Retiro desde cuenta bancaria)
- La empresa envia dinero desde sus cuentas bancarias a un cliente

#### Tipo 5: BANCOS → PLATAFORMAS (Transferencia entre cuentas de la empresa)
- Movimiento interno de la empresa entre sus cuentas

#### Tipo 6: PLATAFORMAS → PLATAFORMAS (Transferencia interna)
- Movimiento entre plataformas de la misma empresa

**El VERIFICADOR DE USD (columnas M-P) es un sistema de conciliacion:**

| Verificacion | Que hace | Formula |
|-------------|----------|---------|
| USD RECIBIDO | Suma lo que CLIENTES enviaron a la empresa | `SUMIF(ORIGEN2="CLIENTES", MONTO)` |
| USD PAGADO | Suma lo que la empresa pago a CLIENTES | `SUMIFS(MONO, ORIGEN2="PLATAFORMAS", DESTINO="CLIENTES")` |
| USD PAGADO CLIENTES | Suma transferencias entre clientes | `SUMIFS(MONO, ORIGEN2="CLIENTES", DESTINO="CLIENTES")` |
| Diferencia USD |平衡 entre recibido y pagado | `=RECIBIDO - PAGADO` |

**El VERIFICADOR DE PLATAFORMAS (filas 8-10) cruza con la hoja PLATAFORMAS:**
- INGRESOS DOLARES vs INGRESOS PLATAFORMAS → debe coincidir
- EGRESOS DOLARES vs EGRESOS PLATAFORMAS → debe coincidir

### 4.2 Hoja BOLIVARES - El Motor de Negocio

Esta es la hoja **mas critica** porque aqui se genera la ganancia de la empresa.

#### 4.2.1 La Operacion Base: Compra/Venta de Dolares con Bolivares

Cada fila de transaccion tiene esta estructura:

```
ORIGEN → EMISOR → MONEDA=BS → BOLIVARES1(monto BS enviado) → DESTINO → RECEPTOR → MONEDA2 → DOLARES2(monto USD recibido) → TASA2(tasa)
```

**Ejemplo real (Fila 3):**
```
BANCOS (VZLA JOHA) → envia 19,500 BS → CLIENTES (EVELIO RAMIREZ) → recibe 50 USD a tasa 390
```

**Significado**: Evelio le vendio $50 a la empresa. La empresa le pago 19,500 bolivares (50 * 390 = 19,500). La empresa compro dolares baratos.

**Ejemplo real (Fila 4):**
```
BANCOS (VZLA JOHA) → envia 31,200 BS → CLIENTES (EVELIO RAMIREZ) → recibe 80 USD a tasa 390
```

**Ejemplo real (Fila 5):**
```
BANCOS (MERC KAR) → envia 80,000 BS → CLIENTES (KAROL MALDONADO) → recibe 200 USD a tasa 400
```

#### 4.2.2 La Comision por Pago Móvil (0.3%)

Cada transaccion en bolivares genera una comision automatica:

```
COMISION = BOLIVARES1 * 0.003
```

Ejemplo: Si una transaccion es de 100,000 BS, la comision es 300 BS.

Esta comision se acumula en `=SUM(BOLIVARES[COMISION])` y se usa para:
- Calcular los ingresos por comisiones
- Distribuir entre los operadores

#### 4.2.3 La Ganancia Directa (Columna R)

Solo se calcula cuando **ORIGEN=CLIENTES y DESTINO=CLIENTES**:

```
GANANCIA DIRECTA = DOLARES2_recibido - DOLARES_enviado
```

Esto representa la ganancia de la empresa cuando un cliente le compra dolares a otro cliente a traves de ella.

#### 4.2.4 La Conversion de Bolivares a Dolares (Columna N)

```
DOLARES2 = BOLIVARES2 / TASA2
```

Esta formula convierte los bolivares recibidos a su equivalente en dolares usando el tipo de cambio del momento.

---

## 5. Los 5 Verificadores de la Hoja BOLIVARES

### 5.1 Verificador de USD x Bolívares (Filas T-V, iniciando fila 3)

**Propósito**: Medir cuanto dinero en dolares entro y salio del negocio por concepto de compra/venta de bolivares.

| Concepto | Formula | Valor Actual |
|----------|---------|-------------|
| **VENDIDO** (U3) | `SUMIF(ORIGEN="CLIENTES", DOLARES)` | $0 |
| **COMPRADO** (U4) | `SUMIF(DESTINO="CLIENTES", DOLARES2)` | $455 |
| **RECIBIDOS EN BANCOS** (U7) | `SUM(Tabla16[INGRESO]) - AB3` | 100,000 BS |
| **ENVIADO DESDE BANCOS** (U8) | `SUM(Tabla16[EGRESO]) - AB3` | 130,700 BS |
| **TOTAL INGRESOS** (U14) | `SUMIF(ORIGEN="CLIENTES", BOLIVARES1)` | 150,000 BS |
| **TOTAL EGRESOS** (U15) | `SUMIF(DESTINO="CLIENTES", BOLIVARES2)` | 180,700 BS |
| **REMANENTE** (U17) | `(U14+U19) - U15 - U21 - U20` | 5,240,910.61 BS |
| **ACUMULADO** (U19) | `Tabla16[[#Totals],[ACUMULADO]]` | 5,271,610.61 BS |
| **COMPRAR** (U32) | `U31 / U46` | #DIV/0! (falta tasa) |
| **GANANCIA** (U33) | `U32 + U29` | #DIV/0! |

**Logica**: El REMANENTE es lo que queda de bolivares disponibles despues de cubrir todos los egresos. Se usa para calcular cuantos dolares se pueden comprar.

### 5.2 Verificador de Bolívares en Bancos (Filas T-W, filas 6-11)

**Propósito**: Verificar que el dinero que entro por bancos coincida con lo que se gasto.

| Concepto | Formula | Valor |
|----------|---------|-------|
| **RECIBIDOS EN BANCOS** (U7) | `SUM(Tabla16[INGRESO]) - AB3` | 100,000 |
| **VENTA A CUENTAS** (V7) | `Z3` (ingresos por ventas) | 100,000 |
| **DIF** (W7) | `U7 - V7` | 0 ✓ |
| **ENVIADO DESDE BANCOS** (U8) | `SUM(Tabla16[EGRESO]) - AB3` | 130,700 |
| **EGRESOS TOTALES** (V8) | `SUM(Z8:Z10) - Z4` | 130,700 |
| **DIF** (W8) | `U8 - V8` | 0 ✓ |
| **ENVIOS BS CLIENTE A CLIENTE** (U11) | `SUMIFS(BOLIVARES1, ORIGEN="CLIENTES", DESTINO="CLIENTES")` | 50,000 |
| **VERIFICACION** (V11) | `SUM(BOLIVARES1)+SUM(COMISION) - Z3 - AB3 - V8 - Z4` | 50,000 |
| **DIF** (W11) | `U11 - V11` | 0 ✓ |

**Logica**: Cada diferencia (DIF) debe ser 0. Si no lo es, hay un error de conciliacion.

### 5.3 Verificador de Bolívares Totales (Fila 13)

Verifica que los totales de la hoja BOLIVARES cuadren con los de la hoja BANCOS.

### 5.4 Distribucion de Ingresos (Filas Y-Z, filas 3-5)

**Propósito**: Clasificar de donde viene el dinero.

| Concepto | Formula | Valor |
|----------|---------|-------|
| **INGRESOS POR VENTAS** | `SUMIFS(BOLIVARES1, ORIGEN="CLIENTES", DESTINO="BANCOS")` | 100,000 |
| **INGRESOS PARA PAGO DE GASTOS** | `SUMIF(DESTINO="GASTOS", BOLIVARES2)` | 0 |
| **INGRESOS POR PAGO DE INTERESES** | `SUMIF(EMISOR=referencia, BOLIVARES1)` | 0 |

**Logica**: Los ingresos vienen de 3 fuentes:
1. Clientes que venden dolares a la empresa (y pagan en BS)
2. Pagos de gastos (que la empresa cobra)
3. Intereses ganados

### 5.5 Distribucion de Egresos (Filas Y-Z, filas 7-11)

**Propósito**: Clasificar en que se gasta el dinero.

| Concepto | Formula | Valor |
|----------|---------|-------|
| **EGRESOS POR COMPRAS** | `SUMIFS(BOLIVARES1, ORIGEN="BANCOS", DESTINO="CLIENTES")` | 130,700 |
| **EGRESOS POR GASTOS** | `SUM(Z13:Z20)` | 0 |
| **COMISIONES POR PM** | `SUMIF(ORIGEN="BANCOS", COMISION)` | 0 |

**Logica**: El principal gasto es comprar dolares para vender a clientes.

---

## 6. La Cadena de Valor Completa

### 6.1 Ejemplo Real: Como se Genera una Gananica

**Paso 1: Un cliente vende dolares a la empresa**
```
Fila 3: VZLA JOHA envia 19,500 BS → EVELIO RAMIREZ recibe $50
Tasa: 390 BS/USD
La empresa compro $50 a 390 BS/USD
```

**Paso 2: La empresa vende esos dolares a otro cliente**
(Supongamos que vende $50 a 420 BS/USD)
```
Ingreso: 50 * 420 = 21,000 BS
Costo: 19,500 BS
Ganancia bruta: 1,500 BS
Comision PM (0.3%): 21,000 * 0.003 = 63 BS
Ganancia neta: 1,437 BS
```

**Paso 3: La empresa le paga al primer cliente**
```
Evelio recibe sus 19,500 BS en su cuenta bancaria
```

### 6.2 Flujo de Cambio de Divisa (Hoja CAMBIOS)

**Ejemplo real (Fila 3):**
```
SARAH ALVAREZ solicita cambiar EUR → CASH
RECIBIDO: 900 (euros que la empresa recibe)
ENVIAR: 1,000 (dolares que la empresa envia)
DIFERENCIA: -100 (la empresa perdio 100 dolares)
PORCENTAJE: -10%
```

**Analisis**: Este cambio fue PERDIDOSO para la empresa. Sarah le dio 900 euros y la empresa le entrego 1,000 dolares. La empresa perdio $100 en esta operacion.

**Donde se acumula la ganancia/perdida de cambios:**
```
COMISIONES RECIBIDAS = SUM(CAMBIOS[DIFERENCIA]) = -100
COSTOS DE ENVIOS = SUM(CAMBIOS[COSTOS DE ENVIO]) = 0
GANANCIA NETA = -100 - 0 = -100
```

### 6.3 Flujo de Pago de Nomina

La nomina se calcula automaticamente en la seccion "DISTRIBUCION DE LA NOMINA" de BOLIVARES:

| Empleado | Formula BS | Formula USD |
|----------|-----------|-------------|
| KAROL MALDONADO | `SUMIF(RECEPTOR="KAROL MALDONADO", BOLIVARES2)` | `SUMIF(RECEPTOR="KAROL MALDONADO", MONTO2)` |
| MIGUEL RAMONES | `SUMIF(RECEPTOR="MIGUEL RAMONES", BOLIVARES2)` | `SUMIF(RECEPTOR="MIGUEL RAMONES", MONTO2)` |
| SARAH ALVAREZ | `SUMIF(RECEPTOR="SARAH ALVAREZ", BOLIVARES2)` | `SUMIF(RECEPTOR="SARAH ALVAREZ", MONTO2)` |
| SERGIO LOPEZ | `SUMIF(RECEPTOR="SERGIO LOPEZ", BOLIVARES2)` | `SUMIF(RECEPTOR="SERGIO LOPEZ", MONTO2)` |
| YENSI RIOS | `SUMIF(RECEPTOR="YENSI RIOS", BOLIVARES2)` | `SUMIF(RECEPTOR="YENSI RIOS", MONTO2)` |
| BEATRIZ TORREALBA | `SUMIF(RECEPTOR="BEATRIZ TORREALBA", BOLIVARES2)` | `SUMIF(RECEPTOR="BEATRIZ TORREALBA", MONTO2)` |

**Logica**: Cada vez que un empleado aparece como RECEPTOR en una transaccion BOLIVARES, se suma a su nomina.

### 6.4 Flujo de Comisiones y Alquileres

La seccion "DISTRIBUCION DE LAS COMISIONES" calcula:

| Persona | Comision BS | Comision USD | Alquiler |
|---------|------------|-------------|----------|
| EDUARD OCHOA | `SUMIF(RECEPTOR, BOLIVARES2)` | `SUMIF(RECEPTOR, MONTO2)` | `SUMIF(RECEPTOR, BOLIVARES2)` (alquiler) |
| KAROL MALDONADO | ... | ... | ... |
| SARAH ALVAREZ | ... | ... | ... |
| YENSI RIOS | ... | ... | ... |
| BEATRIZ TORREALBA | ... | ... | ... |

---

## 7. El Sistema de Conciliacion Bancaria

### 7.1 Como Funciona

Cada cuenta bancaria tiene:
```
SALDO = ACUMULADO + INGRESO - EGRESO
```

Donde:
- **ACUMULADO**: Saldo inicial conocido (se ingresa manualmente)
- **INGRESO**: `SUMIF(BOLIVARES[RECEPTOR]=banco, BOLIVARES[BOLIVARES1])` - Todo lo que llego al banco
- **EGRESO**: `SUMIF(BOLIVARES[EMISOR]=banco, BOLIVARES[BOLIVARES1])` - Todo lo que salio del banco

### 7.2 El Campo DISPONIBLE y la DIFERENCIA

Solo **VZLA JOHA** tiene DISPONIBLE = 1,755,332.88. Esto significa:
- El saldo calculado por formulas es 1,755,332.88
- El saldo real en el banco es 1,755,332.88
- DIFERENCIA = 0 (cuadra)

**Para todas las demas cuentas**: DISPONIBLE esta vacio, lo que significa que **no se esta verificando** el saldo real contra el calculado. La DIFERENCIA es igual al SALDO porque no hay DISPONIBLE.

### 7.3 Distribucion de Bancos por Persona

La fila 42-45 de BOLIVARES asigna bancos a personas:

**KAROL MALDONADO** (fila 42):
```
BANCOS!D3 + D9 + D15 + D18 + D20 + D23 + D25 + D27
= BAN ANA K + BAN KAR + MERC KAR + PROV KAR + VZLA ALE + VZLA KAR + VZLA ANA K + VZLA BEL
```

**SARAH ALVAREZ** (fila 43):
```
BANCOS!D10 + D11 + D19 + D26
= BAN SAR + MER SAR + PROV SAR + VZLA SAR
```

**BEATRIZ TORREALBA** (fila 44):
```
BANCOS!D5 = BAN BEA
```

**ANA KARINA MORENO** (fila 45):
```
BANCOS!D21 + D4 = VZLA ANA K + BAN ANA K
```

**Luego calcula la comision de cada persona:**
```
Total Bancos de la Persona - Nomina Pagada = Ganancia Bruta
Comision = Ganancia Bruta * 0.003
Comision Neta = Comision - Costo Nomina USD
```

---

## 8. El Modelo de Negocio Completo

### 8.1 Fuentes de Ingreso

| Fuente | Descripcion | Como se calcula |
|--------|-------------|-----------------|
| **Spread de compra/venta** | Comprar dolares baratos, vender caros | Diferencia entre tasa de compra y venta |
| **Comision por transaccion BS** | 0.3% por cada pago movil | `BOLIVARES1 * 0.003` |
| **Comision por cambio de divisa** | Diferencia entre RECIBIDO y ENVIAR | `=RECIBIDO - ENVIAR` en CAMBIOS |
| **Comision por transferencia** | Por mover dinero entre cuentas | Variable |

### 8.2 Fuentes de Egreso

| Fuente | Descripcion |
|--------|-------------|
| **Nomina de operadores** | Pago a empleados (Karol, Sarah, Sergio, etc.) |
| **Alquileres** | Pago de alquileres (Eduard Ochoa, Karol, Sarah) |
| **Comisiones de pago movil** | Comisiones que cobran los bancos |
| **Gastos operativos** | Combis, carreras, servicios |
| **Contratacion de servicios** | Servicios externos |
| **Remodelacion** | Inversion en infraestructura |
| **Perdidas** | Cambios de divisa con perdida |

### 8.3 Metricas Clave que Calcula el Sistema

| Metrica | Formula | Que mide |
|---------|---------|----------|
| **REMANENTE** | `(INGRESOS + ACUMULADO) - EGRESOS - GASTOS - COMISIONES` | Dinero disponible para operar |
| **USD a comprar** | `REMANENTE / TASA` | Cuantos dolares se pueden comprar con el remanente |
| **GANANCIA** | `USD comprados + DIF` | Ganancia total del periodo |
| **Promedio de compras** | `BS gastados en compras a clientes` | Costo promedio |
| **DIF BS** | `VENTA A CUENTAS - BS USADOS` | Diferencia entre lo que entro y lo que se uso |

### 8.4 El Numero Magico: 420

En varias formulas aparece el numero **420** como divisor:
```
V19 = U19 / 420  →  5,271,610.61 / 420 = 12,551.45 USD
V27 = (Tabla16[ACUMULADO] / 420) + U27  →  12,551.45 USD
V37 = V36 / 300  →  5,240,910.61 / 300 = 17,469.70 USD
AF42 = AC42 / 300  →  Comision en USD
```

**420 es el tipo de cambio BS/USD** que se usa para convertir bolivares a dolares en los calculos de resumen. **300** parece ser otro tipo de cambio utilizado en contextos diferentes (posiblemente la tasa del mercado paralelo vs la tasa oficial).

---

## 9. Diagrama de Flujo Completo del Dinero

```
┌──────────────────────────────────────────────────────────────────────┐
│                        ENTRADAS DE DINERO                           │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  CLIENTES venden USD ──→ BANCOS (BS) ──→ COMPRA DE DOLARES          │
│  (Evelio, Karol)        (100,000 BS)     ($455 comprados)           │
│                                                                      │
│  CLIENTES venden BS ──→ BANCOS ──→ RECIBIDOS EN BANCOS              │
│  (Karol: 150,000 BS)               (100,000 BS)                     │
│                                                                      │
│  CLIENTES pagan EUR ──→ CAMBIOS ──→ COMISIONES RECIBIDAS            │
│  (Sarah: 900 EUR)      (perdida    (-100 USD)                       │
│                         de -100)                                     │
│                                                                      │
│  TOTAL INGRESOS: 150,000 BS                                          │
│  ACUMULADO: 5,271,610.61 BS                                          │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│                        SALIDAS DE DINERO                             │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  COMPRA DE DOLARES ──→ CLIENTES                                     │
│  (130,700 BS usados)  (Karol: $200, Sarah: $125)                    │
│                                                                      │
│  ENVIOS BS CLIENTE A CLIENTE                                         │
│  (Karol → Sarah: 50,000 BS)                                         │
│                                                                      │
│  EGRESOS POR COMPRAS: 130,700 BS                                    │
│  EGRESOS POR GASTOS: 0 BS                                           │
│  COMISIONES POR PM: 0 BS                                            │
│                                                                      │
│  TOTAL EGRESOS: 180,700 BS                                          │
│  TOTAL DOLARES PAGADOS: $455                                        │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│                     BALANCE FINAL                                    │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  REMANENTE = (150,000 + 5,271,610.61) - 180,700 - 0 - 0            │
│            = 5,240,910.61 BS                                         │
│                                                                      │
│  Equivalente USD: 5,240,910.61 / 420 = $12,551.45                  │
│                                                                      │
│  DOLARES EN SISTEMA:                                                │
│  - Comprados de bancos: $64,182                                     │
│  - Disponibles en BS: $17,469.70                                    │
│  - TOTAL: $81,651.70                                                │
│  - Menos vendidos a clientes: $12,551.45                            │
│  - DIFERENCIA: $69,100.25                                           │
│                                                                      │
│  BANCOS TOTALES:                                                    │
│  - Acumulado: 5,271,610.61 BS                                       │
│  - Saldo: 5,240,910.61 BS                                           │
│  - Disponible (VZLA JOHA): 1,755,332.88 BS                         │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 10. Relaciones entre Hojas (El Arma Secreta)

### 10.1 Diagrama de Dependencias

```
LISTAS ──────────────────────────────────────────────────────┐
  (valores de referencia)                                      │
  │                                                           │
  ├──→ BOLIVARES (usa ORIGEN, DESTINO, CLIENTES, BANCOS)      │
  │       │                                                   │
  │       ├──→ CLIENTES (SUMIF por nombre de cliente)         │
  │       │                                                   │
  │       ├──→ BANCOS (SUMIF por nombre de banco)             │
  │       │       │                                           │
  │       │       └──→ FILA 42-45 BOLIVARES (asigna bancos    │
  │       │              a personas)                           │
  │       │                                                   │
  │       └──→ PLATAFORMAS (SUMIF por nombre de plataforma)   │
  │                                                           │
  ├──→ DOLARES (usa ORIGEN, DESTINO)                          │
  │       │                                                   │
  │       └──→ VERIFICADOR contra PLATAFORMAS                 │
  │                                                           │
  └──→ CAMBIOS (usa SOLICITANTES)                             │
          │                                                   │
          └──→ CLIENTES[COMISION]                             │
```

### 10.2 Tablas Internas

Las formulas hacen referencia a tablas con nombres `Tabla3`, `Tabla4`, `Tabla10`, `Tabla12`, `Tabla14`, `Tabla16`, `Tabla17`:

| Tabla | Ubicacion | Contenido | Uso Principal |
|-------|-----------|-----------|---------------|
| **Tabla3** | CLIENTES | Lista de nombres de clientes | `Tabla3[[#This Row],[CLIENTES]]` |
| **Tabla4** | BANCOS | Lista de nombres de bancos | `Tabla4[[#This Row],[BANCOS]]` |
| **Tabla10** | PLATAFORMAS | Lista de nombres de plataformas | `Tabla10[[#This Row],[PLATAFORMAS]]` |
| **Tabla12** | DOLARES | Datos de transacciones en dolares | Referenciada en verificadores |
| **Tabla14** | CLIENTES | Datos completos de clientes | `Tabla14[[#This Row],[PENDIENTE]]` etc. |
| **Tabla16** | BANCOS | Datos completos de bancos | `Tabla16[[#Totals],[ACUMULADO]]` |
| **Tabla17** | PLATAFORMAS | Datos completos de plataformas | `Tabla17[[#Totals],[INGRESOS]]` |

---

## 11. Casos de Uso Reales en el Datos

### 11.1 Operacion 1: Evelio vende $50
```
Fecha: 19/01/2026
Origen: VZLA JOHA (Banco de Johana en Venezuela)
Monto: 19,500 BS
Tasa: 390 BS/USD
Destino: CLIENTES → EVELIO RAMIREZ
Dolares recibidos: $50
Referencia: 5271
```
**Interpretacion**: La empresa le compro $50 a Evelio. Le pago 19,500 BS via pago movil desde la cuenta VZLA JOHA.

### 11.2 Operacion 2: Evelio vende $80 mas
```
Fecha: 19/01/2026
Origen: VZLA JOHA
Monto: 31,200 BS
Tasa: 390 BS/USD
Destino: CLIENTES → EVELIO RAMIREZ
Dolares recibidos: $80
Referencia: 8505
```
**Interpretacion**: Segunda compra de dolares a Evelio el mismo dia.

### 11.3 Operacion 3: Karol vende $200
```
Fecha: 19/01/2026
Origen: MERC KAR (Mercantil de Karol)
Monto: 80,000 BS
Tasa: 400 BS/USD
Destino: CLIENTES → KAROL MALDONADO
Dolares recibidos: $200
Referencia: 9530
```
**Interpretacion**: Karol le vende $200 a la empresa desde su cuenta Mercantil. La empresa le paga 80,000 BS.

### 11.4 Operacion 4: Karol deposita 100,000 BS
```
Fecha: 19/01/2026
Origen: CLIENTES → KAROL MALDONADO
Monto: 100,000 BS
Tasa: 500 BS/USD
Destino: BANCOS → BAN JOH
```
**Interpretacion**: Karol deposita 100,000 BS a la cuenta BAN JOH de la empresa. La tasa de 500 indica que esperaba recibir $200 a cambio (100,000 / 500 = 200).

### 11.5 Operacion 5: Karol transfiere 50,000 BS a Sarah
```
Fecha: 19/01/2026
Origen: CLIENTES → KAROL MALDONADO
Monto: 50,000 BS
Tasa: 500 BS/USD
Destino: CLIENTES → SARAH ALVAREZ
Dolares recibidos: $125
Referencia: 9874
```
**Interpretacion**: Karol pide a la empresa que transfiera 50,000 BS a Sarah. A cambio, Sarah le da $125 (50,000 / 400 = 125).

### 11.6 Cambio de Divisa 1: Sarah cambia EUR a CASH
```
Fecha: 15/07/2026
Tipo: CASH/EUR
Solicitante: SARAH ALVAREZ
Recibido: 900 (EUR)
Enviar: 1,000 (USD)
Diferencia: -100
```
**Interpretacion**: Sarah quiere cambiar 900 EUR a dolares. La empresa le da 1,000 USD. **La empresa pierde $100** en esta operacion. Esto puede ser un favor, un error, o parte de un acuerdo mas amplio.

---

## 12. Resumen Ejecutivo del Modelo de Negocio

### Que hace esta empresa:
1. **Compra dolares** a clientes a una tasa (ej: 390 BS/USD)
2. **Vende dolares** a clientes a una tasa mayor (ej: 400+ BS/USD)
3. **cobra comision del 0.3%** por cada transaccion en bolivares
4. **Cobra comisiones por cambios de divisa** (la diferencia entre RECIBIDO y ENVIAR)
5. **Maneja cuentas bancarias** de sus operadores para facilitar pagos
6. **Paga nomina** a sus operadores
7. **Paga gastos** (alquileres, servicios, remodelacion)

### Donde gana dinero:
- **Spread de compra/venta de dolares** (la fuente principal)
- **Comisiones por transacciones** (0.3% por pago movil)
- **Margen en cambios de divisa** (cuando la diferencia es positiva)

### Donde pierde dinero:
- **Cambios de divisa con perdida** (como el caso Sarah: -100 USD)
- **Gastos operativos** (nomina, alquileres, servicios)

### El numero clave:
```
ACUMULADO TOTAL EN BANCOS: 5,271,610.61 BS
EQUIVALENTE EN USD: ~$12,551 (a tasa 420)
DOLARES COMPRADOS DE BANCOS: $64,182
TOTAL USD EN SISTEMA: ~$81,652
```

La empresa opera con **~$81,652 en dolares** y **~5.2 millones de bolivares** en sus cuentas bancarias.
