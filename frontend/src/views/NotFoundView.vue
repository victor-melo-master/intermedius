<template>
  <div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
      <span class="text-6xl block mb-4">🔍</span>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Página no encontrada</h1>
      <p class="text-gray-500 mb-6">La ruta que buscas no existe.</p>
      <router-link to="/dashboard" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
        Volver al inicio
      </router-link>
    </div>
  </div>
</template>
