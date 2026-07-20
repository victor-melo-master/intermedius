# Flujo de dinero y propósito de cada cálculo

Este documento responde a dos preguntas para cada proceso del Excel:
1. **¿Hacia dónde se mueve el dinero?** (de qué entidad a qué entidad)
2. **¿Para qué sirve cada cálculo?** (qué pregunta de negocio responde, no solo qué fórmula usa)

---

## 1. Mapa general — las 4 "cajas" donde puede estar el dinero

Todo el sistema es un juego de mover valor entre **4 tipos de cajas**:

```
┌─────────────┐        ┌─────────────┐        ┌──────────────┐        ┌─────────┐
│   CLIENTES   │◄──────►│    BANCOS    │◄──────►│  PLATAFORMAS  │        │  GASTOS  │
│ (personas    │        │ (cuentas Bs  │        │ (wallets USD, │        │ (nómina, │
│  externas)   │        │  del negocio)│        │  el negocio)  │        │  alquiler│
└─────────────┘        └─────────────┘        └──────────────┘        │  etc.)   │
       ▲                                                                └─────────┘
       │                                                                     ▲
       └─────────────────── el dinero también puede ir directo a Gastos ────┘
```

- **CLIENTES** = el mundo exterior (personas con las que el negocio transa).
- **BANCOS** = las cuentas bancarias en bolívares que son del negocio.
- **PLATAFORMAS** = las wallets/canales en USD (u otras divisas) que son del negocio.
- **GASTOS** = una caja de "salida final" del dinero (nómina, alquiler, comisiones, etc.), no
  vuelve a circular.

**Regla de oro para leer cualquier fila de las tablas transaccionales:**
> `ORIGEN` = de dónde sale el dinero. `DESTINO` = a dónde llega.
> Si ambos son "del negocio" (BANCOS↔PLATAFORMAS), es un movimiento interno.
> Si uno de los dos es "CLIENTES", es una transacción real con el mundo exterior.

---

## 2. Las 3 "tuberías" de movimiento (hojas transaccionales) y qué dinero mueve cada una

| Hoja | Qué tipo de dinero mueve | Entre qué cajas |
|---|---|---|
| **DOLARES** | USD / divisas en cash o wallets | CLIENTES ↔ PLATAFORMAS ↔ CLIENTES |
| **BOLIVARES** | Bolívares (con su equivalente en USD vía tasa) | CLIENTES ↔ BANCOS ↔ GASTOS |
| **CAMBIOS** | Arbitraje/canje de divisas (sin pasar por Bs) | CLIENTES ↔ (el negocio, sin caja fija) |

Ninguna hoja mueve dinero *dentro* de una sola caja (por ejemplo, no hay "cliente a cliente
directo sin registro" — todo pasa por una fila con ORIGEN y DESTINO explícitos).

---

## 3. Proceso DOLARES — flujo de dinero explicado

### 3.1 Los 3 caminos posibles que puede tomar el dinero en esta hoja

```
Camino A — Fondeo de cliente:
  PLATAFORMA (del negocio) ──dinero──► CLIENTE
  Ejemplo real: CASH BELKIS ──1000 USD──► SARAH ALVAREZ
  Propósito: el negocio le está entregando dólares en efectivo/wallet a un cliente
             (esto es lo que en la hoja CLIENTES se ve reflejado como "RETIRO" del cliente).

Camino B — Cliente paga a otro cliente (con el negocio de intermediario en el registro):
  CLIENTE A ──dinero──► CLIENTE B
  Ejemplo real: SARAH ALVAREZ ──900 EUR──► EVELIO RAMIREZ
  Propósito: mover dinero entre dos personas usando al negocio como "cámara de compensación"
             (se refleja como "PAGO" en A y "RETIRO" en B, en la hoja CLIENTES).

Camino C — Cliente devuelve/entrega dinero al negocio:
  CLIENTE ──dinero──► PLATAFORMA (del negocio)
  Propósito: el cliente está pagando algo (una deuda, una compra) y el dinero entra
             a una wallet del negocio (se refleja como "PAGO" del cliente e "INGRESOS"
             de esa plataforma).
```

### 3.2 Para qué sirve cada cálculo de esta hoja

| Cálculo | Para qué sirve (propósito de negocio) |
|---|---|
| `MONTO2 = MONTO` | Garantizar que lo que sale del origen es exactamente lo que entra al destino (sin pérdida ni comisión oculta en el trayecto). |
| `USD RECIBIDO` vs `USD PAGADO` (verificador) | Confirmar que **todo dólar que salió de una plataforma** efectivamente quedó registrado como **pago recibido por algún cliente** — detecta dinero "fantasma" que salió pero no se sabe a quién llegó. |
| `USD PAGADO CLIENTES` | Aislar específicamente cuánto dinero se movió **entre clientes** (sin tocar cajas del negocio), útil para saber cuánto volumen de intermediación pura (sin fondeo propio) se hizo. |
| `VERIFICADOR DE PLATAFORMAS` (ingresos/egresos) | Confirmar que el saldo que la hoja PLATAFORMAS calcula (por su cuenta, sumando otra columna) coincide con lo que esta hoja dice que debería ser — es un doble-chequeo cruzado entre dos hojas independientes, para atrapar errores de captura antes de que se acumulen. |

---

## 4. Proceso BOLIVARES — flujo de dinero explicado

Esta es la hoja donde ocurre el **negocio principal**: comprar y vender bolívares contra
dólares, ganando por el spread de tasas.

### 4.1 Los 4 caminos posibles

```
Camino A — El negocio le compra bolívares a un cliente (el cliente "vende su USD" a cambio de Bs):
  CLIENTE ──Bs──► BANCO (del negocio)
  El cliente entrega bolívares (o equivalente) y el banco del negocio los recibe.
  Ejemplo real: KAROL MALDONADO ──100.000 Bs──► BAN JOH
  Propósito de negocio: "ingreso por ventas" — es la forma principal en que entra dinero
  fresco al negocio.

Camino B — El negocio le paga/vende bolívares a un cliente:
  BANCO (del negocio) ──Bs──► CLIENTE
  Ejemplo real: VZLA JOHA ──19.500 Bs──► EVELIO RAMIREZ (equivalente a 50 USD a tasa 390)
  Propósito de negocio: "egreso por compras" — el negocio le está pagando en bolívares
  a un cliente, usualmente porque ese cliente le vendió dólares.

Camino C — El negocio paga un gasto operativo en bolívares:
  BANCO (del negocio) ──Bs──► GASTOS (nómina, alquiler, comisiones, servicios...)
  Propósito de negocio: registrar la salida de dinero que NO vuelve a circular
  (sostener la operación del negocio, no generar ganancia).

Camino D — Arbitraje directo entre dos clientes (el negocio hace de puente sin quedarse el dinero):
  CLIENTE A ──Bs──► CLIENTE B  (con dos tasas distintas)
  Ejemplo real: KAROL MALDONADO ──50.000 Bs──► SARAH ALVAREZ (tasa compra 500, tasa venta 400)
  Propósito de negocio: el negocio "presta" su intermediación para conectar a dos clientes,
  ganando o perdiendo la diferencia entre la tasa a la que "compró" (le compró a A) y la
  tasa a la que "vendió" (le entregó a B) — esto es justamente lo que calcula GANANCIA DIRECTA.
```

### 4.2 Para qué sirve cada cálculo — la lógica de negocio real detrás de cada número

| Cálculo | Para qué sirve |
|---|---|
| `DOLARES = BOLIVARES1 / TASA` | Traducir lo que entra en bolívares a su valor en dólares, **desde el punto de vista de quién entrega los bolívares** (el emisor) — sirve para saber cuánto "vale" en USD lo que el cliente/banco está entregando. |
| `DOLARES2 = BOLIVARES2 / TASA2` | Lo mismo pero **desde el punto de vista de quién recibe** — sirve para saber cuánto USD "recibe" en valor el destinatario. |
| `COMISION = BOLIVARES1 * 0.3%` | Es el **ingreso fijo garantizado** del negocio por facilitar la operación, independiente de si hay ganancia o pérdida por tasas — sirve para separar "ganancia por servicio" de "ganancia por especulación de tasa". |
| `GANANCIA DIRECTA` | Responde: *"¿cuánto ganó o perdió el negocio por la diferencia de tasas entre lo que compró y lo que vendió, en operaciones cliente-a-cliente?"* — es el corazón del margen del negocio de cambio. |
| `VERIFICADOR DE USD X BOLIVARES` (VENDIDO/COMPRADO/DIF) | Responde: *"¿el total de dólares que 'vendimos' (entregamos en Bs) es coherente con el total que 'compramos' (recibimos en Bs)?"* — permite detectar si se está regalando más de lo que se está recibiendo. |
| `RECIBIDOS EN BANCOS` / `ENVIADO DESDE BANCOS` | Responde: *"¿el dinero que entró/salió de los bancos según la hoja BANCOS coincide con lo que dice la hoja BOLIVARES?"* — doble-chequeo entre hojas, igual que en DOLARES. |
| `ENVIOS BS DE CLIENTES A CLIENTES` | Aísla el volumen de arbitraje puro (Camino D) — sirve para medir cuánto negocio viene de "conectar gente" vs. de operar con las cuentas propias. |
| `TOTAL INGRESOS` / `TOTAL EGRESOS` | Responde: *"¿cuánto bolívar fresco entró vs. cuánto salió en el período?"* — el pulso de caja del negocio en Bs. |
| `REMANENTE` | Responde: *"¿cuánto dinero le queda al negocio en bolívares, considerando lo que tenía + lo que entró - lo que salió - los gastos?"* — es el **patrimonio en Bs al cierre**, el número más importante para saber si el negocio está sano. |
| `ACUMULADO` (global) | Es el saldo consolidado de **todas las cuentas bancarias juntas** — sirve como "punto de partida" para el cálculo del REMANENTE, y también como saldo inicial del siguiente período. |
| `DISTRIBUCION DE LOS INGRESOS` (ventas / gastos / intereses) | Responde: *"de todo lo que entró, ¿cuánto vino de ventas normales vs. de un cliente pagando un gasto vs. de intereses?"* — sirve para desglosar el origen del ingreso, no solo su monto total. |
| `DISTRIBUCION DE LOS EGRESOS` (compras / gastos / comisiones PM) | Responde lo mismo pero del lado de salidas: *"¿cuánto salió por comprarle a clientes vs. por gastos operativos vs. por comisiones de pago móvil?"* |
| `DISTRIBUCION DE LA NOMINA` (por empleado) | Responde: *"¿cuánto se le ha pagado a cada empleado en este período, en Bs y en USD?"* — control de nómina sin necesitar una hoja de RRHH aparte. |
| `DISTRIBUCION DE LAS COMISIONES` (por socio) | Responde la pregunta más delicada: *"¿cuánto le corresponde retirar/cobrar a cada socio, según el rendimiento de las cuentas bancarias que tiene asignadas?"* — es el cálculo de reparto de utilidades entre los dueños del negocio. |

---

## 5. Proceso CAMBIOS — flujo de dinero explicado

### 5.1 El único camino de esta hoja

```
CLIENTE (solicita un cambio) ◄──► El negocio (actúa como casa de cambio pura, sin pasar por Bs/bancos)

  El cliente "entrega" una cosa (ENVIAR) y "recibe" otra (RECIBIDO), en distintas divisas
  o canales (ej. CASH por EUR, USDT por CASH).

  Ejemplo real: SARAH ALVAREZ pide cambiar → el negocio RECIBE 900, tiene que ENVIAR 1000
  (según el par CASH/EUR).
```

Nota importante: esta hoja **no especifica un banco/plataforma concreto** como sí lo hacen
DOLARES y BOLIVARES — es un registro más "conceptual" del resultado neto de la operación de
cambio, independientemente de por dónde físicamente se movió el dinero (eso se asume ya
registrado, si aplica, en DOLARES).

### 5.2 Para qué sirve cada cálculo

| Cálculo | Para qué sirve |
|---|---|
| `DIFERENCIA = RECIBIDO - ENVIAR` | Responde: *"¿ganamos o perdimos en esta operación de cambio puntual?"* — es el resultado bruto de arbitraje. |
| `PORCENTAJE` | La misma pregunta pero en términos relativos (%), útil para comparar el margen entre operaciones de distinto tamaño. |
| `GANANCIA_REAL = DIFERENCIA - COSTOS_DE_ENVIO` | Responde: *"¿cuánto ganamos de verdad, después de descontar lo que costó operativamente mover el dinero (comisiones de envío, fees de red cripto, etc.)?"* |
| `% REAL` | Lo mismo, en porcentaje — el margen neto real del cambio. |
| `COMISIONES RECIBIDAS` (verificador) | Responde: *"¿la suma de todas las diferencias de esta hoja coincide con lo que se está reflejando como comisión en el estado de cuenta de los clientes?"* — doble-chequeo cruzado con la hoja CLIENTES. |
| `COMPRA TRON` / `DISPONIBLE EN TRON` | Responde una pregunta muy específica: *"¿cuánto USDT (u otro cripto) tenemos disponible ahora mismo en la wallet TRON, después de las compras y los costos de envío asociados?"* — control operativo de una plataforma cripto puntual. |

---

## 6. A dónde va a parar cada cálculo — quién "consume" cada número

Para que quede claro el propósito **final** de cada cálculo (no solo qué mide, sino quién lo usa):

| Cálculo | ¿Quién lo necesita y para qué decisión? |
|---|---|
| `SALDO` de cliente | El operador que atiende al cliente, para saber **cuánto se le debe o cuánto debe** antes de la siguiente operación. |
| `SALDO` de banco/plataforma | El operador que decide **de qué cuenta sacar dinero** para la siguiente operación (evitar sobregirar una cuenta). |
| `DIFERENCIA` (SALDO vs DISPONIBLE) en BANCOS/PLATAFORMAS | Quien concilia las cuentas al final del día, para detectar **fraude, error de captura, o dinero no registrado**. |
| `REMANENTE` / `ACUMULADO` global | El dueño del negocio, para saber **el patrimonio real en bolívares** al cierre del período. |
| `GANANCIA DIRECTA`, `DIFERENCIA` (CAMBIOS) | El dueño del negocio, para saber **cuánto se está ganando por arbitraje/spread**, la fuente principal de utilidad. |
| `DISTRIBUCION DE INGRESOS/EGRESOS` | Contabilidad/administración, para clasificar el dinero sin tener que revisar fila por fila. |
| `DISTRIBUCION DE NOMINA` | Recursos humanos / el dueño, para verificar que a cada empleado se le pagó lo correcto. |
| `DISTRIBUCION DE COMISIONES` (por socio) | Los socios, para saber **cuánto les corresponde retirar** del negocio según el desempeño de sus cuentas asignadas. |
| Todos los `VERIFICADOR...` (DIF, cuadres) | Quien hace el control de calidad/auditoría interna, para **atrapar errores antes de que se acumulen** mes a mes. |

---

## 7. Diagrama consolidado del flujo completo de dinero

```
                         ┌───────────────────────────────────────┐
                         │              CLIENTES                  │
                         │  (compran/venden USD y Bs, cambian     │
                         │   divisas, pagan/retiran)               │
                         └───────────────┬─────────────────────────┘
                                          │
              ┌───────────────────────────┼───────────────────────────┐
              │                            │                            │
     hoja DOLARES                 hoja BOLIVARES                 hoja CAMBIOS
     (mueve USD/cash)           (mueve Bs con tasa)          (arbitraje de divisas)
              │                            │                            │
              ▼                            ▼                            ▼
      ┌───────────────┐           ┌───────────────┐            (resultado neto:
      │  PLATAFORMAS   │           │     BANCOS     │             ganancia/pérdida
      │ (wallets USD   │           │  (cuentas Bs   │              directo a la
      │  del negocio)  │           │  del negocio)  │            hoja CLIENTES,
      └───────────────┘           └───────┬───────┘             columna COMISION)
                                            │
                                            ▼
                                    ┌───────────────┐
                                    │     GASTOS     │
                                    │ (nómina, renta, │
                                    │  comisiones —   │
                                    │  sale del ciclo)│
                                    └───────────────┘

  Todo lo que pasa por estas 3 tuberías se resume en:
    → estado de cuenta por CLIENTE (¿cuánto se le debe?)
    → saldo por BANCO/PLATAFORMA (¿cuánto hay disponible en cada cuenta?)
    → REMANENTE global (¿cuánto vale el negocio en bolívares hoy?)
    → GANANCIA DIRECTA + comisiones (¿cuánto se está ganando, y de dónde?)
    → reparto por SOCIO (¿cuánto le toca retirar a cada dueño?)
```

---

## 8. Para el diseño del API — traducción directa de "propósito" a "endpoint"

| Propósito de negocio | Endpoint sugerido |
|---|---|
| Saber cuánto se le debe a un cliente ahora mismo | `GET /clientes/{id}/saldo` |
| Saber cuánto hay disponible en una cuenta antes de operar | `GET /bancos/{id}/saldo`, `GET /plataformas/{id}/saldo` |
| Detectar errores de captura antes de que se acumulen | `GET /reportes/verificadores` (devuelve todas las diferencias de cuadre, marcando en rojo las que no son 0) |
| Saber el patrimonio actual del negocio en bolívares | `GET /reportes/remanente` |
| Saber cuánto se está ganando por arbitraje/spread | `GET /reportes/ganancias?desde=&hasta=` |
| Saber cuánto le toca retirar a cada socio | `GET /reportes/comisiones-por-socio` |
| Clasificar automáticamente un movimiento sin que el usuario elija categoría | Lógica interna del servicio de creación de movimientos (tabla de mapeo ORIGEN→DESTINO → categoría, ver Proceso 4.2 del documento anterior) |
| Conciliar saldo del sistema vs. saldo real del banco | `POST /bancos/{id}/conciliar` (compara SALDO calculado vs. DISPONIBLE capturado manualmente) |

---

*Este documento se enfoca en el "para qué" de cada número. Para el detalle exacto de cada
fórmula y ejemplo numérico celda por celda, ver `procesos_detallados_excel.md`. Para el
modelo de datos y estructura de endpoints, ver `analisis_procesos_para_api.md`.*
