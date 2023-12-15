<template>
  <FormField :error="error" :help="helpText" :required="required">
    {{ label }}
    <template #component>
      <div class="space-y-1">
        <template v-for="option in computedOptions" :key="option.value">
          <AppCheckbox v-model="localValue" :value="option.value" :disabled="disabled">
            {{ option.label }}
          </AppCheckbox>
        </template>
      </div>
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import { useVModel } from '@vueuse/core'
import useConvertedOptions from '@/composition/useConvertedOptions.js'

const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
const computedOptions = useConvertedOptions(props.options)
</script>
