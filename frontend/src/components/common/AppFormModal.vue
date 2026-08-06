<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/50 backdrop-blur-sm"
          @click="closeOnOverlay && close()"
        ></div>

        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden"
          role="dialog"
          aria-modal="true"
        >
          <div v-if="$slots.header || title" class="flex items-center justify-between px-6 py-4 border-b border-edge shrink-0">
            <div v-if="$slots.header">
              <slot name="header" />
            </div>
            <h2 v-else-if="title" class="text-lg font-semibold text-heading">
              {{ title }}
            </h2>
            <button
              @click="close"
              class="p-1.5 hover:bg-surface-muted rounded-lg text-ink-faint hover:text-ink-muted transition"
              aria-label="Cerrar"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="px-6 py-4 overflow-y-auto flex-1">
            <slot />
          </div>

          <div v-if="$slots.footer" class="px-6 py-4 border-t border-edge shrink-0 flex justify-end gap-2">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, default: '' },
  closeOnOverlay: { type: Boolean, default: true },
  closeOnEscape: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'close'])

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

watch(
  () => props.modelValue,
  (val) => {
    if (val && props.closeOnEscape) {
      const handler = (e) => { if (e.key === 'Escape') close() }
      document.addEventListener('keydown', handler)
      nextTick(() => document.removeEventListener('keydown', handler))
    }
  },
  { immediate: true }
)
</script>
