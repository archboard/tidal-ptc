<template>
  <div class="space-y-0.5">
    <div v-for="(label, value) in options">
      <AppCheckbox v-model="localValue" :value="value">
        {{ label }}
      </AppCheckbox>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useVModel } from '@vueuse/core'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'

const props = defineProps({
  modelValue: Array,
  options: Object,
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)
</script>
