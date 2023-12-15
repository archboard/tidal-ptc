<template>
  <RadioGroup v-model="localValue">
    <div class="-space-y-px rounded-md bg-white dark:bg-gray-900">
      <RadioGroupOption as="template" v-for="(option, optionIndex) in options" :key="option.label" :value="option.value" v-slot="{ checked, active }">
        <div :class="[optionIndex === 0 ? 'rounded-tl-md rounded-tr-md' : '', optionIndex === options.length - 1 ? 'rounded-bl-md rounded-br-md' : '', checked ? 'z-10 border-primary-200 dark:border-primary-700 bg-primary-50 dark:bg-primary-900' : 'border-gray-200 dark:border-gray-700', 'relative flex cursor-pointer border p-4 focus:outline-none']">
          <span :class="[checked ? 'bg-primary-500 border-transparent' : 'bg-white dark:bg-gray-900 border-gray-300', active ? 'ring-2 ring-offset-2 ring-primary-600 dark:ring-offset-gray-900 dark:ring-primary-400' : '', 'mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded-full border flex items-center justify-center']" aria-hidden="true">
            <span class="rounded-full bg-white dark:bg-gray-900 w-1.5 h-1.5" />
          </span>
          <span class="ml-3 flex flex-col">
            <RadioGroupLabel as="span" :class="[checked ? 'text-primary-900 dark:text-primary-100' : 'text-gray-900 dark:text-gray-100', 'block text-sm font-medium']">{{ option.label }}</RadioGroupLabel>
            <RadioGroupDescription v-if="option.description" as="span" :class="[checked ? 'text-primary-700' : 'text-gray-500', 'block text-sm']">{{ option.description }}</RadioGroupDescription>
          </span>
        </div>
      </RadioGroupOption>
    </div>
  </RadioGroup>
</template>

<script setup>
import { RadioGroup, RadioGroupDescription, RadioGroupLabel, RadioGroupOption } from '@headlessui/vue'
import { useVModel } from '@vueuse/core'

const props = defineProps({
  options: {
    type: Array,
    required: true,
  },
  modelValue: [String, Number, Boolean],
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)
</script>
