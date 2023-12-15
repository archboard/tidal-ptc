<template>
  <FormField :error="error" :help="helpText" :required="required">
    {{ label }}
    <template #component="{ id }">
      <AppDatepicker
        v-model="localValue"
        :has-error="!!error"
        :id="id"
      />
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'
import AppDatepicker from '@/components/forms/AppDatepicker.vue'

const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
</script>
