<template>
  <select
    v-model="localValue"
    @change="e => $emit('change', e)"
    class="block w-full pl-3 pr-8 py-2 text-sm dark:bg-gray-900 focus:outline-none focus:ring-2 rounded-md"
    :class="{
      'focus:ring-primary-500 focus:border-primary-500 dark:focus:border-primary-500 border-gray-300 dark:border-gray-600': !hasError,
      'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': hasError,
    }"
  >
    <option v-if="!hideNull" :value="null" disabled>{{ nullOption }}</option>
    <slot>
      <option v-for="option in options" :value="option.value || option.label">{{ option.label }}</option>
    </slot>
  </select>
</template>

<script setup>
import { useVModel } from '@vueuse/core'

const props = defineProps({
  modelValue: [Object, String, Number, Boolean],
  hasError: {
    type: Boolean,
    default: false,
  },
  options: {
    type: [Array, Object],
    default: () => ([]),
  },
  nullOption: {
    type: String,
    default: 'Select an option',
  },
  hideNull: {
    type: Boolean,
    default: () => false,
  }
})
const emit = defineEmits(['update:modelValue', 'change'])
const localValue = useVModel(props, 'modelValue', emit)
</script>
