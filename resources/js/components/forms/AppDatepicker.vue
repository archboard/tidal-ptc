<template>
  <DatePicker
    v-model="localValue"
    :popover="{ visibility: 'focus' }"
    :is-dark="theme === 'dark'"
    v-bind="$attrs"
  >
    <template v-slot="{ inputValue, inputEvents }">
      <div class="relative">
        <div class="absolute left-0 inset-y-0 flex items-center pl-3 pointer-events-none">
          <CalendarIcon class="text-gray-500 h-4 w-4" />
        </div>

        <AppInput class="pl-10" :value="inputValue" v-on="inputEvents" :has-error="hasError" />

        <div class="absolute right-0 inset-y-0 flex items-center justify-center" :class="{ 'pr-4': !hasError, 'pr-8': hasError }">
          <button @click.prevent="localValue = null" class="flex items-center justify-center">
            <span class="sr-only">Clear date</span>
            <TrashIcon class="text-red-500 h-4 w-4" />
          </button>
        </div>
      </div>
    </template>
  </DatePicker>
</template>

<script setup>
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'
import AppInput from '@/components/forms/AppInput.vue'
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import { useVModel } from '@vueuse/core'
import { TrashIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import { useLocalStorage } from '@vueuse/core'

const theme = useLocalStorage('theme', 'light')
const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
