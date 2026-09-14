<template>
  <div class="relative">
    <div :class="['absolute left-0 inset-y-0 flex items-center pl-3 pointer-events-none', hasError ? 'text-red-500' : 'text-gray-500']">
      <CalendarDaysIcon class="h-4 w-4" />
    </div>

    <AppInput
      class="pl-10"
      :type="isDateTime ? 'datetime-local' : 'date'"
      :model-value="inputValue"
      @update:model-value="onInput"
      v-bind="$attrs"
      :id="id"
      :has-error="hasError"
      :disabled="disabled"
    />

    <div class="absolute right-0 inset-y-0 flex items-center justify-center" :class="{ 'pr-4': !hasError, 'pr-10': hasError }">
      <button @click.prevent="clearValue" class="flex items-center justify-center">
        <span class="sr-only">Clear date</span>
        <TrashIcon class="text-red-500 h-4 w-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AppInput from '@/components/forms/AppInput.vue'
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import { useVModel } from '@vueuse/core'
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'
import { TrashIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  ...fieldProps,
  mode: {
    type: String,
    default: 'date',
  }
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
const isDateTime = computed(() => props.mode.toLowerCase().includes('time'))

// ponytail: the app speaks 'YYYY-MM-DD HH:mm', the native input speaks 'YYYY-MM-DDTHH:mm'
const inputValue = computed(() => (localValue.value ?? '').replace(' ', 'T'))
const onInput = (value) => {
  localValue.value = value ? value.replace('T', ' ') : null
}
const clearValue = () => {
  if (!props.disabled) {
    localValue.value = null
  }
}
</script>
