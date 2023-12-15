<template>
  <AppCombobox
    v-model="country"
    v-model:query="query"
    :options="filteredCountries"
    :displayValue="displayValue"
    :has-error="hasError"
  >
    <template v-slot:item="{ item }">
      {{ displayValue(item) }}
    </template>
  </AppCombobox>
</template>

<script setup>
import AppCombobox from '@/components/forms/AppCombobox.vue'
import { useVModel } from '@vueuse/core'
import fetchesCountries from '@/composition/fetchesCountries.js'
import { ref, computed, watch } from 'vue'

const props = defineProps({
  // We're going to assume that the model value should be the ID
  modelValue: [Number, String],
  displayValue: {
    type: Function,
    default: (item) => item?.name,
  },
  hasError: {
    type: Boolean,
    default: () => false,
  }
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)
const { countries, fetchCountries } = fetchesCountries()
const query = ref('')
const country = ref({})
const filteredCountries = computed(() => {
  if (!query.value) {
    return countries.value
  }

  return countries.value.filter(c => {
    return c.name.toLowerCase().includes(
      query.value.toLowerCase()
    )
  })
})

// Fetch the country listing
fetchCountries().then(() => {
  // If there was an existing model value,
  // we need to set the country to display
  // the original value
  if (localValue.value) {
    country.value = countries.value.find(c => c.id === localValue.value)
  }
})

watch(country, value => {
  localValue.value = value?.id
})
</script>
