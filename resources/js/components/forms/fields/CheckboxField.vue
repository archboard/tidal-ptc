<template>
  <FormField :error="error" :help="helpText">
    <template #component>
      <AppCheckbox v-model="localValue" :required="required">
        {{ label }}
      </AppCheckbox>
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import { useVModel } from '@vueuse/core'

const props = defineProps({
  ...fieldProps,
  modelValue: [Boolean, String, Number, Array],
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
