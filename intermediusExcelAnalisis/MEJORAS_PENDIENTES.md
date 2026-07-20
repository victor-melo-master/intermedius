# Mejoras Pendientes - Análisis Excel Intermedius

## 1. Diagrama ERD de Datos

Crear un diagrama Entidad-Relación que muestre las relaciones entre:
- CLIENTES ↔ BOLIVARES (EMISOR/RECEPTOR)
- CLIENTES ↔ DOLARES (EMISOR2/RECEPTOR)
- CLIENTES ↔ CAMBIOS (SOLICITANTES)
- BANCOS ↔ BOLIVARES (RECEPTOR/EMISOR)
- PLATAFORMAS ↔ DOLARES (RECEPTOR/EMISOR2)
- LISTAS ↔ todas las hojas (validación de datos)

Incluir cardinalidades y tipos de relación.

## 2. Documentación de Edge Cases

Definir comportamiento esperado ante situaciones no estándar:
- ¿Qué pasa si MONEDA ≠ BS en la hoja BOLIVARES?
- ¿Qué pasa con campos vacíos en ORIGEN/DESTINO?
- ¿Cómo se manejan transacciones con TASA = 0?
- ¿Qué pasa si un nombre en EMISOR/RECEPTOR no existe en LISTAS?
- ¿Cómo se manejan montos negativos?
- ¿Qué pasa si una referencia (REF) está duplicada?

## 3. Ciclo Temporal y Cierre de Período

Documentar:
- ¿Cuándo se "cierra" un período contable (mensual, quincenal)?
- ¿Cómo se pasa el ACUMULADO de BANCOS de un período al siguiente?
- ¿Se resetean las transacciones o se acumulan?
- ¿Cómo se genera el archivo del siguiente mes?
- ¿Se archiva el anterior?

## 4. Validación de Datos en LISTAS

Explicar las reglas de validación:
- ¿Qué tipo de validación usa cada columna de LISTAS (data validation)?
- ¿Qué pasa si se ingresa un valor no válido en un dropdown?
- ¿Hay protecciones de hoja o celdas?
- ¿Se usan reglas condicionales para resaltar errores?
