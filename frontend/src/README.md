# src/ — Código fuente

## Estructura

```
src/
├── api/
│   └── axios.js              # Instancia Axios con interceptor de auth token
├── components/               # 11 componentes reutilizables
├── router/
│   └── index.js              # 19 rutas (login + 8 secciones protegidas)
├── stores/                   # 9 Pinia stores
├── views/                    # 16 vistas/páginas
├── App.vue                   # Componente raíz
├── index.css                 # Tailwind + estilos globales
└── main.js                   # Entry point
```

## Convenciones

- Archivos en inglés, textos visibles en español
- Stores: `use{Name}Store` — `defineStore('name', () => { ... })`
- Componentes: PascalCase, props con `defineProps`, eventos con `defineEmits`
- API: instancia axios única en `api/axios.js` con interceptor de token
- Moneda: `Intl.NumberFormat('en', { minimumFractionDigits: 2 })`
- Fechas: `toLocaleDateString('es-VE', ...)`
