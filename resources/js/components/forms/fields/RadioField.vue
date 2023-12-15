<template>
  <FormField :error="error" :help="helpText" :required="required">
    {{ label }}
    <template #component>
      <div class="space-y-1">
        <template v-for="option in computedOptions" :key="option.value">
          <AppRadio v-model="localValue" :value="option.value" :disabled="disabled" :name="name">
            {{ option.label }}
          </AppRadio>
        </template>
      </div>
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'
import useConvertedOptions from '@/composition/useConvertedOptions.js'
import AppRadio from '@/components/forms/AppRadio.vue'
import { nanoid } from 'nanoid'

const props = defineProps({
  ...fieldProps,
  modelValue: [Boolean, String, Number],
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
const computedOptions = useConvertedOptions(props.options)
const name = 'radio_' + nanoid(4)
</script>
