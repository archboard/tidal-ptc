<template>
  <Teleport to="body">
    <div class="fixed z-40 inset-0 overflow-y-auto">
      <div class="flex items-start justify-center min-h-screen pt-12 px-4 pb-20 text-center">
        <transition
          enter-active-class="duration-300 ease-out"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="duration-200 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-if="show" @click="$emit('close')" class="fixed inset-0 transition-opacity backdrop-blur-sm" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-800 opacity-75"></div>
          </div>
        </transition>

        <slot />
      </div>
    </div>
  </teleport>
</template>

<script setup>
const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
})
const emit = defineEmits(['close'])
</script>
