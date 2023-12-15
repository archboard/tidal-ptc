<template>
  <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
    <FadeInGroup>
      <template v-for="field in fields" :key="field.key">
        <div :class="getSpanClass(field)">
          <component
            :is="FormFields[field.component]"
            v-model="localForm[field.key]"
            :error="errors[errorPrefix + field.key]"
            :options="field.options"
            :label="field.label"
            :placeholder="field.placeholder"
            :required="field.required"
            :disabled="field.disabled"
            :help="field.help"
          />
        </div>
      </template>
    </FadeInGroup>
  </div>
</template>

<script setup>
import * as FormFields from '@/components/forms/fields'
import { useVModel } from '@vueuse/core'
import FadeInGroup from '@/components/transitions/FadeInGroup.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  fields: {
    type: Array,
    required: true,
  },
  errors: {
    type: Object,
    required: true,
  },
  errorPrefix: {
    type: String,
    default: '',
  },
})
const emit = defineEmits(['update:modelValue'])
const localForm = useVModel(props, 'modelValue', emit)
const getSpanClass = (field) => {
  const spans = {
    1: 'md:col-span-1',
    2: 'md:col-span-2',
    3: 'md:col-span-3',
    4: 'md:col-span-4',
    5: 'md:col-span-5',
    6: 'md:col-span-6',
  }

  return spans[field.span] || 'md:col-span-6'
}
// const localForm = computed({})
</script>
