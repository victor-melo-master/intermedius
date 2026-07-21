# docs/ — API Documentation

| Archivo | Descripción |
|---|---|
| `api-reference.md` | Documentación completa de endpoints, request/response, ejemplos |

### Cambios recientes — Flujo Multi-Paso

Ver sección *"Flujo Multi-Paso (Operaciones con Transacciones)"* en `api-reference.md` para:

- Endpoints: `solicitud`, `iniciar`, `cerrar`, `cancelar`
- Transacciones CRUD: `POST`, `PUT`, `DELETE`, `confirmar`, `revertir`
- State machine: `solicitud → en_progreso → cerrada / cancelada`
- Nuevos campos en `OperacionResource`: `estado`, `monto_solicitado`, `moneda_operacion`, `transacciones`
- Nuevo `TransaccionResource`
- Validación de balance al cerrar
- Migración `moneda_operacion_id`

### Verificación (Legacy)

Sección *"Verificación de Operaciones (Legacy)"*:

- `GET /verificacion` — vista con movimientos, transacciones y saldos
- `POST /iniciar-verificacion` — `sin_verificar → en_verificacion`
- `PATCH /movimientos/{id}/validar` — valida movimiento individual
- `PATCH /movimientos/{id}/rechazar` — rechaza movimiento con motivo
- `PATCH /transacciones/{id}/validar` — valida transacción (legacy)

### Documentos, Cuentas y Clientes

Secciones nuevas en `api-reference.md`:

- Documentos: `preview`, `download`, CRUD por cliente
- Cuentas: `POST /{cuenta}/saldo`, `GET /{cuenta}/saldo-disponible`
- Clientes: `GET /{cliente}/operaciones`, `POST /{cliente}/operaciones/exportar`, `POST /{cliente}/restaurar`

### Direccionalidad compra/venta

La semántica es desde la perspectiva de **la casa de cambio**:

- **Compra** = la casa **compra** divisa del cliente
  - Transacción divisa: Cliente → Intermedius (origen=cliente, destino=intermedius)
  - Transacción VES: Intermedius → Cliente (origen=intermedius, destino=cliente)
- **Venta** = la casa **vende** divisa al cliente
  - Transacción divisa: Intermedius → Cliente (origen=intermedius, destino=cliente)
  - Transacción VES: Cliente → Intermedius (origen=cliente, destino=intermedius)

Ver tabla detallada en *"Dirección de Transacciones"* dentro de `api-reference.md`.
