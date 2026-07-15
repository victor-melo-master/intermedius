# Recomendaciones de Tests — Intermedius API

## Estado Actual

- **216 tests existentes**, todos pasan (warnings PDO deprecados, no son failures)
- **~570 assertions** en 25 archivos (16 Feature + 8 Unit + 1 TestCase base)
- **0 tests frontend** (sin framework configurado)
- **Configuración**: PHPUnit 10.5, SQLite `:memory:`, RefreshDatabase

---

## GAP 1: Módulos sin tests (PRIORIDAD ALTA)

### 1.1 CategoriaGastoEndpointTest.php — NO EXISTE

El controller `CategoriaGastoController` tiene CRUD completo con policies.

```php
// tests/Feature/CategoriaGastoEndpointTest.php

// Happy path
test_index_lista_categorias_ordenadas_por_nombre()          // GET /categorias-gasto → 200, ordenadas por nombre
test_store_crea_categoria()                                  // POST → 201, con titular relationship
test_show_muestra_categoria_con_titular()                    // GET /{id} → 200, con titular
test_update_modifica_categoria()                             // PATCH → 200, valor actualizado
test_destroy_elimina_categoria()                             // DELETE → 204, Model missing

// Auth
test_index_requiere_autenticacion()                          // → 401

// Validation
test_store_requiere_nombre()                                 // body vacío → 422
test_store_requiere_tipo_gasto()                             // sin tipo_gasto → 422

// Authorization
test_store_admin_solo()                                      // operador → 403
test_update_operador_no_puede()                              // operador → 403
test_destroy_operador_no_puede()                             // operador → 403

// 404
test_show_404_si_no_existe()                                 // ID inexistente → 404
```

### 1.2 DocumentoEndpointTest.php — NO EXISTE

El controller `Api/V1/DocumentoController` tiene `index`, `store`, `destroy`, `preview`, `download`.

```php
// tests/Feature/DocumentoEndpointTest.php

// Happy path
test_index_lista_documentos_del_cliente()                    // GET /clientes/{id}/documentos → 200
test_store_sube_documento()                                  // POST con archivo → 201
test_destroy_elimina_documento()                             // DELETE → 204

// Auth (sesión)
test_index_requiere_autenticacion()                          // → 401
test_store_requiere_autenticacion()                          // → 401
test_destroy_requiere_autenticacion()                        // → 401

// Auth (token query param)
test_preview_requiere_token_valido()                         // sin token → 401
test_preview_404_token_invalido()                            // token malo → 401
test_download_requiere_token_valido()                        // sin token → 401

// Validation
test_store_requiere_archivo()                                // sin archivo → 422
test_store_requiere_tipo_documento()                         // sin tipo → 422

// 404
test_destroy_404_si_no_existe()                              // ID inexistente → 404
```

---

## GAP 2: Tests de autorización rol `lectura` (PRIORIDAD ALTA)

Todas las vistas CRUD faltan test cases para el rol `lectura`. Patrón estándar a repetir en cada controller.

| Controller | lectura index | lectura show | lectura no store | lectura no update | lectura no destroy |
|---|---|---|---|---|---|
| BancoController | ❌ | ❌ | ❌ | ❌ | ❌ |
| CuentaController | ❌ | ❌ | ❌ | ❌ | ❌ |
| MonedaController | ❌ | ❌ | ❌ | ❌ | ❌ |
| TitularController | — | ❌ | — | ✅ | ✅ |
| ClienteController | ❌ | ❌ | ❌ | ✅ | ❌ |
| CategoriaGastoController | ❌ | ❌ | ❌ | ❌ | ❌ |

**Tests a agregar por cada controller** (~5 tests x 6 controllers = 30 tests):

```php
test_lectura_puede_listar()        // GET / → 200
test_lectura_puede_ver()          // GET /{id} → 200
test_lectura_no_puede_crear()     // POST → 403
test_lectura_no_puede_editar()    // PATCH → 403
test_lectura_no_puede_eliminar()  // DELETE → 403
```

---

## GAP 3: Tests de validación de duplicados (PRIORIDAD ALTA)

Ningún test actual valida constraints únicos.

| Modelo | Campo(s) único(s) | Test propuesto |
|---|---|---|
| Banco | `nombre` | `test_store_nombre_duplicado_falla` → 422 |
| Moneda | `codigo` | `test_store_codigo_duplicado_falla` → 422 |
| Titular | `nombre` | `test_store_nombre_duplicado_falla` → 422 |
| User | `email` | ✅ Ya existe en UserEndpointTest |
| ComisionCuenta | `cuenta_id` + vigencia | `test_store_duplica_comision_vigente_falla` |
| ComisionOperador | `operador_id` + vigencia | `test_store_duplica_comision_vigente_falla` |
| ComisionMetodoPago | `metodo_pago` + vigencia | `test_store_duplica_comision_vigente_falla` |

---

## GAP 4: Tests Unitarios de Services (PRIORIDAD MEDIA)

### 4.1 CalculadorComisionesServiceTest — faltan 8 tests

```php
// tests/Unit/Services/CalculadorComisionesServiceTest.php

test_comision_cuenta_monto_fijo_persiste()
// ComisionCuenta con tipo_calculo=monto_fijo → monto se registra directo

test_comision_cuenta_aplica_a_ingreso()
// Direccion "ingreso" → comision se resta de ganancia de ingreso

test_comision_cuenta_aplica_a_ambos()
// Direccion "ambos" → comision aplica en ambos sentidos

test_comision_cuenta_match_por_banco_id()
// Sin cuenta_id directo, matchea por banco_id de la cuenta

test_comision_cuenta_no_vigente_no_aplica()
// Comision fuera de vigente_desde/vigente_hasta → no se aplica

test_comision_operador_porcentaje()
// ComisionOperador con tipo_calculo=porcentaje → calcula sobre monto

test_comision_metodo_pago_aplica()
// ComisionMetodoPago completa (0 tests actuales en esta rama)

test_multiples_tipos_comision_simultaneos()
// Combinar cuenta + operador + método de pago → todos se aplican
```

### 4.2 RegistroOperacionServiceTest — faltan 8 tests

```php
// tests/Unit/Services/RegistroOperacionServiceTest.php

test_actualizar_modifica_referencia()
// actualizar($operacion, $data) → referencia cambia

test_registrar_tipo_no_encontrado_falla()
// tipo_codigo="XYZ" inexistente → 404/exception

test_registrar_venta_usd_con_un_movimiento_falla()
// 1 movimiento para tipo que requiere 2 → ValidationException

test_registrar_intermediada_se_registra()
// tipo intermediada → branch registrarIntermediada (0 tests)

test_resolver_tasa_sin_vigente_usa_ultima()
// Sin tasa vigente, toma la última publicada → sin_tasa_referencia=true

test_resolver_tasa_sin_ninguna_lanza_excepcion()
// 0 tasas publicadas → ValidationException

test_resolver_tasa_desfavorable_sin_justificacion_falla()
// Tasa desfavorable + descripción vacía → ValidationException

test_tolerancia_exactamente_0_01_se_acepta()
// Descuadre de exactamente 0.01 USD → se acepta (boundary)
```

### 4.3 PoolServiceTest — faltan 7 tests

```php
// tests/Unit/Services/Pool/PoolServiceTest.php

test_tomar_operaciones_sin_pendientes_devuelve_vacio()
// Sin operaciones pendientes → colección vacía, notifier no llamado

test_tomar_operaciones_con_limit()
// limit=1 con 5 pendientes → solo toma 1

test_pagar_operacion_con_transacciones_no_validadas_falla()
// Transacciones pendientes (no validadas) → ValidationException

test_cancelar_operacion_ya_pagada_falla()
// Estado pagada → assertPuedeCancelar rechaza

test_cancelar_operacion_ya_cancelada_falla()
// Doble cancelación → ValidationException

test_events_son_dispatchados()
// OperacionAsignada, OperacionPagada, OperacionSoltada → Event::assertDispatched

test_notifier_es_llamado_en_asignacion()
// PoolNotifier mock → assertOperacionesAsignadas called
```

### 4.4 TransaccionServiceTest — faltan 6 tests

```php
// tests/Unit/Services/Transaccion/TransaccionServiceTest.php

test_crear_transacciones_lote_multiple()
// crearTransacciones con 2+ items → count correcto, orden

test_saldo_exactamente_igual_al_monto_pasa()
// saldo == monto → no exception (boundary)

test_validar_transaccion_ya_rechazada_falla()
// rechazada → intentar validar → exception

test_cancelar_transaccion_validada_falla()
// validada → intentar cancelar → exception

test_cambiar_cuenta_destino_con_tx_no_pendiente_falla()
// Estado no-pendiente → exception

test_cambiar_cuenta_origen_saldo_insuficiente_falla()
// Nuevo origen con saldo insuficiente → exception
```

### 4.5 SaldoValidatorTest — faltan 5 tests

```php
// tests/Unit/Services/Transaccion/SaldoValidatorTest.php

test_transacciones_validadas_destino_suman_al_saldo()
// TX entrantes validadas → saldo aumenta

test_transacciones_rechazada_cancelada_no_afectan()
// TX rechazadas/canceladas → no modifican saldo

test_cache_null_si_nunca_se_configuro()
// saldo_cache_at = null → calcula en vivo siempre

test_saldo_negativo_se_retorna_correctamente()
// saldo_cache < 0 → retorna negativo

test_cuenta_no_encontrada_lanza_excepcion()
// Cuenta ID inexistente → ModelNotFoundException
```

### 4.6 PoolValidatorTest — faltan 5 tests

```php
// tests/Unit/Services/Pool/PoolValidatorTest.php

test_assert_todas_transacciones_validadas_con_pendientes_falla()
// Mezcla validada+pendiente → ValidationException

test_assert_puede_cancelar_estado_pagada_falla()
// Pagada → no se puede cancelar

test_assert_puede_cancelar_estado_cancelada_falla()
// Ya cancelada → no se puede cancelar de nuevo

test_assert_puede_pagar_otro_pagador_falla()
// Pagador distinto al asignado → exception

test_usuario_inactivo_lanza_excepcion()
// User activo=false → exception en asserts de pool
```

---

## GAP 5: Tests de Edge Cases en Feature (PRIORIDAD MEDIA)

### 5.1 OperacionEndpointTest — faltan 6 tests

```php
test_store_con_movimientos_vacios_falla()                   // movimientos=[] → 422
test_store_con_un_solo_movimiento_falla()                   // 1 movimiento → 422
test_update_404_si_no_existe()                              // ID inexistente → 404
test_verificar_operacion_cancelada_falla()                  // op cancelada → no se puede verificar
test_verificar_por_admin_pasa()                             // admin puede verificar
test_store_ganancia_se_calcula_correctamente()              // ganancia_bruta_usd/ves correctos
```

### 5.2 PoolControllerTest — faltan 7 tests

```php
test_tomar_404_si_no_existe()                               // operacion inexistente → 404
test_tomar_operador_no_puede()                              // operador → 403
test_tomar_lectura_no_puede()                               // lectura → 403
test_pagada_no_se_puede_retomar()                           // pagada → tomarsoltar/pagar → 422
test_cancelada_no_se_puede_retomar()                        // cancelada → tomar/soltar/pagar → 422
test_mis_ordenes_vacio()                                    // sin órdenes → []
test_super_admin_puede_cancelar()                           // super_admin cancela → 200
```

### 5.3 TasaDiariaEndpointTest — faltan 5 tests

```php
test_venta_menor_compra_con_notas_suficientes_pasa()       // venta < compra con notas ≥ 10 → 201
test_venta_igual_compra_pasa()                              // venta == compra → 201
test_venta_negativa_falla()                                 // tasa negativa → 422
test_historial_retorna_tasas_pasadas()                      // GET historial → 200, registros
test_super_admin_puede_publicar()                           // super_admin → 201
```

### 5.4 GastoEndpointTest — faltan 8 tests

```php
test_gasto_con_multiples_movimientos()                      // 2+ movimientos → 201
test_gasto_show()                                           // GET /gastos/{id} → 200
test_gasto_con_categoria_inexistente_falla()                // categoria_gasto_id fake → 422
test_gasto_monto_cero_falla()                               // monto=0 → 422
test_gasto_lectura_no_puede_crear()                         // lectura → 403
test_index_filtrado_por_categoria()                         // ?categoria_gasto_id=X → filtra
test_index_filtrado_por_fecha()                             // ?fecha_desde=&fecha_hasta= → filtra
test_verificar_gasto()                                      // verificar endpoint
```

---

## GAP 6: Tests de ComisionConfig (PRIORIDAD MEDIA)

`ComisionConfigEndpointTest` solo tiene 5 tests. Faltan:

```php
// CRUD completo para los 3 tipos de comisión

// Index
test_index_lista_comisiones_cuenta()                        // GET → 200
test_index_lista_comisiones_operador()                      // GET → 200
test_index_lista_comisiones_metodo_pago()                   // GET → 200

// Update
test_update_comision_cuenta()                               // PATCH → 200
test_update_comision_operador()                             // PATCH → 200
test_update_comision_metodo_pago()                          // PATCH → 200

// Destroy
test_destroy_comision_cuenta()                              // DELETE → 204
test_destroy_comision_operador()                            // DELETE → 204
test_destroy_comision_metodo_pago()                         // DELETE → 204

// Auth
test_unauthenticated_falla_en_todos()                       // sin token → 401 (3 endpoints)

// Authorization
test_lectura_no_puede_crear()                               // → 403

// Validation
test_tipo_calculo_invalido_falla()                          // tipo="xyz" → 422
test_valor_negativo_falla()                                 // monto=-1 → 422
```

---

## GAP 7: Tests de TasasMercadoService (PRIORIDAD BAJA)

```php
// tests/Unit/Services/TasasMercadoServiceTest.php

test_obtener_paralelo_retorna_null_si_falla()               // API paralelo 500 → null
test_binance_p2p_con_un_solo_elemento()                     // 1 precio → mediana = ese precio
test_binance_p2p_con_dos_elementos()                        // 2 precios → promedio
test_bcv_promedio_clave_faltante()                          // Sin key "promedio" → fallback
```

---

## Resumen Ejecutivo

| Categoría | Existentes | Propuestos | Total Post-Implementación |
|---|---|---|---|
| Feature — CRUD endpoints | 78 | **42** | 120 |
| Feature — Auth/Pool/Operacion | 83 | **28** | 111 |
| Feature — Sin cobertura (CategoriaGasto, Documento) | 0 | **22** | 22 |
| Unit — Services | 72 | **39** | 111 |
| **TOTAL** | **216** | **~131** | **~347** |

---

## Plan de Implementación

### Sprint 1 — Cobertura base (22 tests)
- [ ] `CategoriaGastoEndpointTest.php` — 12 tests
- [ ] `DocumentoEndpointTest.php` — 10 tests

### Sprint 2 — Autorización rol lectura (30 tests)
- [ ] Tests de lectura en BancoController — 5 tests
- [ ] Tests de lectura en CuentaController — 5 tests
- [ ] Tests de lectura en MonedaController — 5 tests
- [ ] Tests de lectura en TitularController — 3 tests (faltantes)
- [ ] Tests de lectura en ClienteController — 3 tests
- [ ] Tests de lectura en CategoriaGastoController — 5 tests
- [ ] Tests de duplicados — 4 tests

### Sprint 3 — ComisionConfig CRUD (13 tests)
- [ ] ComisionConfigEndpointTest — completar index/update/destroy/auth/validation

### Sprint 4 — Unit tests de Services (39 tests)
- [ ] CalculadorComisionesServiceTest — 8 tests
- [ ] RegistroOperacionServiceTest — 8 tests
- [ ] PoolServiceTest — 7 tests
- [ ] TransaccionServiceTest — 6 tests
- [ ] SaldoValidatorTest — 5 tests
- [ ] PoolValidatorTest — 5 tests

### Sprint 5 — Edge cases Feature (26 tests)
- [ ] OperacionEndpointTest — 6 tests
- [ ] PoolControllerTest — 7 tests
- [ ] TasaDiariaEndpointTest — 5 tests
- [ ] GastoEndpointTest — 8 tests

### No implementar (bajo valor)
- Tests de performance/concurrencia (fuera del alcance de PHPUnit)
- Tests de CSRF/XSS (responsabilidad de otro tipo de testing)
- Tests de rate limiting (ya cubierto en AuthTest)
- Tests de cache TTL behavior (difícil de testear aisladamente)
