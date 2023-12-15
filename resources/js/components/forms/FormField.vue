<template>
  <div class="space-y-1">
    <AppLabel :for="id" :has-error="!!error">
      <slot /> <Req v-if="required" />
    </AppLabel>

    <div class="relative">
      <slot name="component" :has-error="!!error" :local-value="localValue" :id="id">
        <component
          :is="components[component]"
          v-model="localValue"
          :has-error="!!error"
          :id="id"
          :disabled="disabled"
          v-bind="{
            ...(['AppInput'].includes(component) ? { type, placeholder } : {}),
            ...(['AppSelect'].includes(component) ? { options } : {}),
          }"
        />
      </slot>

      <div v-if="error" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
        <ExclamationCircleIcon class="h-5 w-5 text-red-500 dark:text-red-400" aria-hidden="true" />
      </div>
    </div>
    <FieldError v-if="error">
      {{ error }}
    </FieldError>

    <slot name="after">
      <HelpText v-if="help">{{ help }}</HelpText>
    </slot>
  </div>
</template>

<script setup>
import { ExclamationCircleIcon } from '@heroicons/vue/24/solid'
import { nanoid } from 'nanoid'
import FieldError from './FieldError.vue'
import Req from './Req.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppDatepicker from '@/components/forms/AppDatepicker.vue'
import AppLabel from '@/components/forms/AppLabel.vue'
import AppInput from '@/components/forms/AppInput.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import { useVModel } from '@vueuse/core'

const components = {
  AppDatepicker,
  AppInput,
  AppSelect,
}

const props = defineProps({
  modelValue: [Object, String, Boolean, Number],
  component: {
    type: String,
    default: 'AppInput',
  },
  id: {
    type: String,
    default: () => nanoid(),
  },
  type: {
    type: String,
    default: 'text',
  },
  error: String,
  placeholder: {
    type: [String, Number],
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: () => false,
  },
  help: {
    type: String,
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  }
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)
</script>
