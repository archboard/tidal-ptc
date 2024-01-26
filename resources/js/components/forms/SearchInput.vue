<template>
  <div class="relative">
    <div :class="['absolute left-0 inset-y-0 flex items-center pl-3 pointer-events-none', hasError ? 'text-red-500' : 'text-gray-500']">
      <MagnifyingGlassIcon class="h-4 w-4" />
    </div>

    <AppInput
      v-model="localValue"
      :placeholder="placeholder || __('Search by name')"
      class="pl-10 w-full"
      :id="id"
      type="search"
      :has-error="hasError"
      :disabled="disabled"
    />
  </div>
</template>

<script setup>
import AppInput from '@/components/forms/AppInput.vue'
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import { useVModel } from '@vueuse/core'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
