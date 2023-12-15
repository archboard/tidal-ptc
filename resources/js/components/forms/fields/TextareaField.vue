<template>
  <FormField
    v-model="localValue"
    component="AppTextarea"
    :error="error"
    :help="helpText"
    :required="required"
    :disabled="disabled"
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
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
