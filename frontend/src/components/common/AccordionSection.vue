<script setup>
import { ref } from 'vue'
import Iconoir from './Iconoir.vue'

const props = defineProps({
  title: { type: String, required: true },
  count: { type: [Number, String], default: null },
  defaultOpen: { type: Boolean, default: true },
})

const emit = defineEmits(['toggle-open'])

const open = ref(props.defaultOpen)

function toggle() {
  open.value = !open.value
  emit('toggle-open', open.value)
}
</script>

<template>
  <div class="border-t-2 border-edge-strong">
    <div class="flex items-center justify-between gap-2">
      <button
        type="button"
        class="flex-1 flex items-center justify-between py-4 group text-left"
        @click="toggle"
        :aria-expanded="open"
      >
        <div class="flex items-center gap-2">
          <h4 class="font-semibold text-heading text-base">{{ title }}</h4>
          <span v-if="count !== null && count !== undefined" class="text-xs bg-surface-soft border border-edge-strong text-ink-muted px-2 py-0.5 rounded-full">{{ count }}</span>
        </div>
        <Iconoir name="chevron-down" class="w-5 h-5 text-ink-muted transition-transform duration-200" :class="{ 'rotate-180': open }" />
      </button>
      <slot name="header-actions" />
    </div>
    <div class="grid transition-all duration-300 ease-in-out" :class="open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'" :aria-hidden="!open">
      <div class="overflow-hidden min-h-0">
        <slot />
      </div>
    </div>
  </div>
</template>
