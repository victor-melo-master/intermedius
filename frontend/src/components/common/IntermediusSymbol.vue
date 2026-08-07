<script setup>
/**
 * IntermediusSymbol — Isótipo de la marca (3 chevrones que forman una "M").
 * Hereda el color del contexto vía currentColor. Geometría tomada de
 * Intermedius_Visual/00 - Intermedius - Logos/Simbolo.
 */
defineProps({
  size: { type: [Number, String], default: 24 },
})
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    :width="size"
    :height="size"
    viewBox="0 0 792 612"
    fill="currentColor"
    aria-hidden="true"
  >
    <polygon points="216.2,169.28 375.68,443.19 460.48,443.19 302.23,169.2" />
    <polygon points="153.62,313.22 233.97,443.19 318.77,443.19 239.65,313.14" />
    <polygon points="368.03,169.28 527.51,443.19 612.31,443.19 454.06,169.2" />
  </svg>
</template>
