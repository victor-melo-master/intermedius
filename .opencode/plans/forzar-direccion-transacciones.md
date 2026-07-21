# Forzar dirección de transacciones según flujo cliente/casa

## Objetivo

Cada transacción debe respetar el patrón de quién entrega qué según el tipo de operación:

| Tipo | Moneda | Origen | Destino |
|------|--------|--------|---------|
| Compra | USD | Intermedius → | → Cliente |
| Compra | VES | Cliente → | → Intermedius |
| Venta | USD | Cliente → | → Intermedius |
| Venta | VES | Intermedius → | → Cliente |

## Archivos a modificar

### 1. `frontend/src/components/operaciones/TransaccionForm.vue`

**Props**: Agregar `esCompra: Boolean`

**Eliminar**: optgroups de cuentasIntermedius/cuentasCliente en el template (los selects ya no agrupan)

**Agregar** en script:
```js
const esCompra = computed(() => props.esCompra)

// Cuando cambia moneda, limpiar origen/destino
watch(() => form.moneda_id, () => {
  form.cuenta_origen_id = ''
  form.cuenta_destino_id = ''
})

// Filtra cuentas según dirección correcta
const cuentasOrigen = computed(() => {
  if (!form.moneda_id) return []
  const deIntermedius = cuentasIntermedius.value.filter(c => c.moneda_id == form.moneda_id)
  const delCliente = cuentasCliente.value.filter(c => c.moneda_id == form.moneda_id)
  const esUSD = (monedasFiltradas.value.find(m => m.id == form.moneda_id)?.codigo) === 'USD'
  if (esCompra.value) return esUSD ? deIntermedius : delCliente
  return esUSD ? delCliente : deIntermedius
})

const cuentasDestino = computed(() => {
  if (!form.moneda_id) return []
  const deIntermedius = cuentasIntermedius.value.filter(c => c.moneda_id == form.moneda_id)
  const delCliente = cuentasCliente.value.filter(c => c.moneda_id == form.moneda_id)
  const esUSD = (monedasFiltradas.value.find(m => m.id == form.moneda_id)?.codigo) === 'USD'
  if (esCompra.value) return esUSD ? delCliente : deIntermedius
  return esUSD ? deIntermedius : delCliente
})

const labelOrigen = computed(() => {
  if (!cuentasOrigen.value.length) return ''
  return cuentasOrigen.value[0]?.titular_id ? 'Cuentas de Intermedius' : 'Cuentas del cliente'
})

const labelDestino = computed(() => {
  if (!cuentasDestino.value.length) return ''
  return cuentasDestino.value[0]?.titular_id ? 'Cuentas de Intermedius' : 'Cuentas del cliente'
})
```

**Template** - reemplazar selects de cuentas:
- Si no hay moneda seleccionada: mostrar texto "Selecciona moneda primero"
- Si hay moneda: mostrar `<select>` con opciones de `cuentasOrigen` / `cuentasDestino`
- Texto pequeño debajo: `labelOrigen` / `labelDestino`

### 2. `frontend/src/views/operaciones/GestionarOperacionView.vue`

En el template de `<TransaccionForm>`:
```html
:es-compra="esCompra"
```

Ya existe `esCompra` en el script de la vista, solo pasarlo como prop.

## Resultado

- El usuario no puede seleccionar cuentas que violen la dirección
- Origen y destino forzados al dueño correcto según operación+moneda
- Sin optgroups ni grupos mezclados — cada dropdown solo muestra un conjunto claro de cuentas
- Al cambiar moneda se resetean origen/destino
