# Agente de Documentación y Análisis del Código

## Propósito
Automatiza la creación y mantenimiento de documentación para el código base de Intermedius, ayudando a comprender el flujo de trabajo principal, modelos de datos y flujos de negocio.

## Estado del Sistema
✅ **Documentación API Principal**: Generada y actualizada automáticamente
✅ **Esquema de Modelos de Base de Datos**: Completo
✅ **Mapa de Rutas**: Documentado

## Funciones

### 1. `./analyze_codebase_structure`
```
--
- Escanea niveles de directorio
- Identifica componentes clave
- Genera árbol de estructura de directorios
- Documenta principales módulos y patrones
```

### 2. `./generate_api_documentation`
```
--
- Escanea controladores, servicios, modelos
- Genera endpoints de API documentados
- Documenta patrones de validación de solicitudes
- Documenta relaciones entre modelos Eloquent
```

### 3. `./document_data_flow`
```
--
- Traza flujo de operaciones desde solicitud hasta BD
- Documenta patrones de procesamiento de operaciones
- Documenta políticas de comisiones
- Documenta lógica de procesamiento de fondos
```

### 4. `./generate_model_relationships`
```
--
- Escanea clases de modelos
- Documenta relaciones BelongsTo/HasMany
- Genera diagrama de relaciones
- Documenta restricciones únicas/compuestas
```

## Datos Requeridos
- Información de configuración de modelo (app/Models/*)
- Información de controladores (app/Http/Controllers/*)
- Información de servicios (app/Services/*)

## Salida
- Markdown estructurado de documentación
- Diagramas MMD/ASCII de flujos de datos
- Referencias cruzadas entre archivos

## Sugerencias de Agentes de Soporte
- `get_soporte_mantenimiento_conceptual()`
- `consultar_patrones_llave_en_mano`

## Preguntas Frecuentes
- ¿Cómo mantener sincronizada la documentación?
- ¿Cómo documentar la lógica de negocio compleja?
- ¿Cómo manejar las relaciones entre módulos?

## Mejoras Futuras Sugeridas
1. `integrar_agente_reconocimiento_patrones()`
2. `desarrollar_documento_diario` para `Intermedius_Diario_Ejecutivo.md`
3. `implementar_reconocimiento_llave_en_mano()`

## Proyecto Asociado
- Agente de `soporte_mantenimiento_conceptual`
- Para mantener sincronizada la `API_Documentation.md` con cambios de código
