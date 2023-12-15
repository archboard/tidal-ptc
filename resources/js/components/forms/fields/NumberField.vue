<template>
  <FormField
    v-model="localValue"
    :error="error"
    :help="helpText"
    type="number"
    :required="required"
    :disabled="disabled"
    :placeholder="placeholder"
  >
    {{ label }}
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'

const props = defineProps({
  ...fieldProps,
  modelValue: Number,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
