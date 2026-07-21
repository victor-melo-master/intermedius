# Intermedius Frontend

SPA en Vue 3 para el sistema de casa de cambio Intermedius.

## Stack

| Componente | Tecnología |
|---|---|
| Framework | Vue 3 (Composition API, `<script setup>`) |
| State | Pinia |
| Router | Vue Router 4 |
| HTTP | Axios |
| CSS | Tailwind CSS |
| Build | Vite |

## Estructura

```
src/
├── api/           # Instancia axios + interceptors
├── components/    # Componentes reutilizables (11)
├── router/        # Configuración de rutas (19)
├── stores/        # Pinia stores (9)
├── views/         # Vistas/páginas (16)
├── App.vue        # Componente raíz
├── index.css      # Estilos globales + Tailwind
└── main.js        # Entry point
```

## Comandos

```bash
npm run dev       # Servidor de desarrollo
npm run build     # Build producción
npm run preview   # Preview build
```

## Documentación

Ver [`docs/`](docs/) para documentación detallada del flujo multi-paso y componentes.
