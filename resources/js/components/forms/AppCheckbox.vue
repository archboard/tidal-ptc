<template>
  <CheckboxWrapper
    :class="{
      'pointer-not-allowed': disabled,
    }"
  >
    <Checkbox v-model="localValue" @change="emitChange" :value="value" :disabled="disabled" />
    <CheckboxText>
      <slot />
      <Req v-if="required" />
    </CheckboxText>
  </CheckboxWrapper>
</template>

<script setup>
import { computed } from 'vue'
import CheckboxWrapper from './CheckboxWrapper.vue'
import Checkbox from './Checkbox.vue'
import CheckboxText from './CheckboxText.vue'
import Req from '@/components/forms/Req.vue'

const props = defineProps({
  modelValue: [Boolean, Array],
  value: [Boolean, String, Number],
  required: Boolean,
  disabled: {
    type: Boolean,
    default: false,
  },
})
const emit = defineEmits(['update:modelValue', 'change'])
const localValue = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})
const emitChange = value => {
  emit('change', value)
}
</script>
