<template>
  <div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
      <img :src="theme.isDark ? logoNegativo : logoPositivo" alt="Intermedius" class="h-14 w-auto mx-auto mb-4" />
      <h1 class="text-2xl font-bold text-heading mb-2">Página no encontrada</h1>
      <p class="text-ink-muted mb-6">La ruta que buscas no existe.</p>
      <router-link to="/dashboard" class="px-4 py-2 bg-gold text-navy hover:bg-gold-dark rounded-lg text-sm font-medium">
        Volver al inicio
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { useThemeStore } from '../stores/theme.js'
import logoPositivo from '../assets/logo-positivo.png'
import logoNegativo from '../assets/logo-negativo.png'

const theme = useThemeStore()
</script>
