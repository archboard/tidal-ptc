<template>
  <DatePicker
    v-model.string="localValue"
    :masks="masks"
    v-bind="$attrs"
    :is-dark="isDark"
    color="primary"
    :popover="popoverOptions"
    :mode="mode"
  >
    <template v-slot="{ inputValue, inputEvents }">
      <div class="relative">
        <div :class="['absolute left-0 inset-y-0 flex items-center pl-3 pointer-events-none', hasError ? 'text-red-500' : 'text-gray-500']">
          <CalendarDaysIcon class="h-4 w-4" />
        </div>

        <AppInput class="pl-10" :value="inputValue" v-on="inputEvents" :id="id" :has-error="hasError" :disabled="disabled" />

        <div class="absolute right-0 inset-y-0 flex items-center justify-center" :class="{ 'pr-4': !hasError, 'pr-10': hasError }">
          <button @click.prevent="clearValue" class="flex items-center justify-center">
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
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'
import { TrashIcon } from '@heroicons/vue/24/solid'
import useColorTheme from '@/composition/useColorTheme.js'
import { watch } from 'vue'

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
const { isDark } = useColorTheme()
const masks = {
  modelValue: props.mode.toLowerCase().includes('time') ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD',
}
const popoverOptions = {
  visibility: 'focus',
  autoHide: false,
}
const clearValue = () => {
  if (!props.disabled) {
    localValue.value = null
  }
}

watch(isDark, console.log)
</script>
