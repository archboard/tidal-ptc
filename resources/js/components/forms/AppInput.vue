<template>
  <input
    v-model="localValue"
    :type="type"
    class="bg-white dark:bg-gray-900 shadow-sm block w-full rounded-lg text-sm focus:outline-none focus:ring-2"
    :class="{
      'border-gray-300 dark:border-gray-600 focus:ring-primary-500 focus:border-primary-500 dark:focus:border-primary-500': !hasError,
      'pr-10 border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': hasError,
    }"
    ref="input"
  >
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useVModel } from '@vueuse/core'

const props = defineProps({
  modelValue: [String, Number],
  type: {
    type: String,
    default: 'text',
  },
  autofocus: {
    type: Boolean,
    default: false
  },
  hasError: {
    type: Boolean,
    default: false,
  }
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)

const input = ref(null)

if (props.autofocus) {
  onMounted(() => {
    input.value.focus()
  })
}
</script>
