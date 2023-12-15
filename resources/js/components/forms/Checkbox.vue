<template>
  <input
    type="checkbox"
    :value="value"
    v-model="localValue"
    @change="$emit('change', localValue)"
    class="cursor-pointer text-primary-500 focus:ring-primary-500 dark:focus:ring-offset-black border-gray-300 dark:border-gray-600 rounded"
    :disabled="disabled"
  >
</template>

<script setup>
import { useVModel } from '@vueuse/core'

const props = defineProps({
  modelValue: {
    type: [Array, Boolean],
    default: false,
  },
  value: {
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})
const emit = defineEmits([
  'update:modelValue',
  'change'
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
