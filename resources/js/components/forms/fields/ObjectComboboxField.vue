<template>
  <FormField :error="error" :help="helpText" :required="required">
    {{ label }}
    <template #component>
      <GenericObjectCombobox
        v-model="localObject"
        v-model:object-id="localValue"
        :options="options"
        :has-error="!!error"
        :disabled="disabled"
      />
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'
import { ref } from 'vue'
import GenericObjectCombobox from '@/components/forms/GenericObjectCombobox.vue'

const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
const localObject = ref(
  props.modelValue
    ? (props.options.find(o => o.id === props.modelValue) || {})
    : {}
)
</script>
