<template>
  <div class="relative">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
      <div class="w-full border-t border-gray-300 dark:border-gray-400"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span :class="`px-2 text-gray-500 dark:text-gray-300 ${background}`">
        <slot />
      </span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  background: {
    type: String,
    default: 'bg-white dark:bg-black',
  }
})
</script>
