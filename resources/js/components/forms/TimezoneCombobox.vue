<template>
  <GenericObjectCombobox
    v-model="timezone"
    v-model:object-id="localValue"
    :options="options"
    search-attribute="name"
    :id="id"
    :has-error="hasError"
  />
</template>

<script setup>
import { ref } from 'vue'
import reduce from 'just-reduce-object'
import GenericObjectCombobox from '@/components/forms/GenericObjectCombobox.vue'
import { useVModel } from '@vueuse/core'
import usesDates from '@/composition/useDates.js'

const props = defineProps({
  modelValue: String,
  id: String,
  hasError: Boolean,
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)
const { fetchTimezones } = usesDates()
const options = ref([])
const timezone = ref({})

fetchTimezones().then(data => {
  options.value = reduce(data, (carry, key, value) => {
    carry.push({
      id: key,
      name: value,
    })
    return carry
  }, [])

  if (localValue.value) {
    timezone.value = {
      id: localValue.value,
      name: data[localValue.value],
    }
  }
})
</script>
