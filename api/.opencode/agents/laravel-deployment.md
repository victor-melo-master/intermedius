# Agente de Despliegue y Configuración de Laravel

## Propósito
Automatiza las tareas comunes relacionadas con la configuración y despliegue de la aplicación Laravel para Intermedius.

## Funciones

### 1. `./setup_laravel_environment`
```
--
- Valida variables de entorno
- Inicializa base de datos (ejecuta migraciones)
- Publica paquete de assets
- Genera clave de aplicación
- Configura almacenamiento
```

### 2. `./deploy_to_production`
```
--
- Construye artefacts optimizados
- Sube a servidor remoto vía SFTP
- Ejecuta migraciones en servidor
- Reinicia servicios
- Ejecuta tareas pendientes
```

### 3. `./environment_generator`
```
--
- Genera .env.py (versión segura)
- Valida formato y valores
- Monitorea cam bios de entorno
```

## Datos Requeridos
- Configuración del servidor remoto
- Variables de entorno
- Procesos del sistema

## Salida
- Logs de estado del despliegue
- Mensajes de error en caso de fallo
- Información de seguimiento

## Preguntas Frecuentes
- ¿Cómo configurar URL del application?
- ¿Cómo manejar configuraciones de diferentes entornos?
- ¿Cómo asegurar proceso de despliegue seguro?
