# src/ — AI Context

## Estructura

```
src/
├── api/               → Cliente HTTP (axios)
├── components/        → 11 componentes reutilizables
├── router/            → 19 rutas con guard de autenticación
├── stores/            → 9 Pinia stores
├── views/             → 16 vistas
├── App.vue            → Componente raíz (solo <router-view />)
├── index.css          → Tailwind directives
└── main.js            → Entry point (createApp, plugins, mount)
```

## Convenciones generales

- Composition API con `<script setup>` en todos los componentes
- Props con `defineProps`, eventos con `defineEmits`, modelos con `defineModel` o `v-model` explícito
- Store siempre `use{Name}Store` con Composition API
- API calls via `api/axios.js` (interceptor agrega token automáticamente)
- Fechas en español (`es-VE`), moneda en formato inglés con 2 decimales
- Sin TypeScript — todo JavaScript vanilla
