<template>
  <span class="m-1 inline-flex items-center rounded-full border border-gray-200 dark:border-gray-500 py-1.5 pl-3 pr-2 text-sm font-medium text-gray-900 dark:text-white" :class="bgColor">
    <span><slot /></span>
    <button @click.prevent="$emit('removed')" type="button" class="ml-1 inline-flex h-4 w-4 flex-shrink-0 rounded-full p-1 text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 hover:text-gray-500 dark:hover:text-gray-300">
      <span class="sr-only">Remove filter</span>
      <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
        <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
      </svg>
    </button>
  </span>
</template>

<script setup>
const props = defineProps({
  bgColor: {
    type: String,
    default: `bg-gray-100 dark:bg-gray-900`
  }
})
const emit = defineEmits(['removed'])
</script>
