<template>
  <div class="inline-flex rounded-md shadow-sm" role="group">
    <button
      v-for="(option, index) in options"
      :key="option.value"
      type="button"
      :aria-pressed="modelValue === option.value"
      class="relative inline-flex items-center gap-1.5 px-3 py-1.5 border text-sm font-medium transition ease-in-out duration-150 focus:z-10 focus:outline-hidden focus:ring-2 focus:ring-primary-500"
      :class="[
        index === 0 ? 'rounded-l-md' : '-ml-px',
        index === options.length - 1 ? 'rounded-r-md' : '',
        modelValue === option.value
          ? 'bg-primary-600 border-primary-600 text-white'
          : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700',
      ]"
      @click="$emit('update:modelValue', option.value)"
    >
      <component v-if="option.icon" :is="option.icon" class="h-4 w-4" aria-hidden="true" />
      <span>{{ option.label }}</span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  /** @type {Array<{ value: string|number, label: string, icon?: Component }>} */
  options: { type: Array, required: true },
})
defineEmits(['update:modelValue'])
</script>
