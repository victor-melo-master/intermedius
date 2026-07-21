# docs/ — API Documentation

| Archivo | Descripción |
|---|---|
| `api-reference.md` | Documentación completa de endpoints, request/response, ejemplos |

### Cambios recientes — Flujo Multi-Paso

Ver sección *"Flujo Multi-Paso (Operaciones con Transacciones)"* en `api-reference.md` para:

- Endpoints: `solicitud`, `iniciar`, `cerrar`, `cancelar`
- Transacciones CRUD: `GET`, `POST`, `PUT`, `DELETE`, `confirmar`, `revertir`
- State machine: `solicitud → en_progreso → cerrada / cancelada`
- Nuevos campos en `OperacionResource`: `estado`, `monto_solicitado`, `moneda_operacion`, `transacciones`
- Nuevo `TransaccionResource`
- Validación de balance al cerrar
- Migración `moneda_operacion_id`
