<template>
  <div class="space-y-4">
    <FormField :error="errors[`${errorPrefix}country_id`]" required>
      {{ $t('Country code') }}
      <template #component="{ hasError }">
        <AppCombobox
          v-model="country"
          v-model:query="query"
          :options="filteredCountries"
          :displayValue="displayValue"
          :has-error="hasError"
        >
          <template v-slot:item="{ item }">
            {{ item.name }} ({{ item.calling_code }})
          </template>
        </AppCombobox>
      </template>
    </FormField>

    <div class="flex justify-between items-start space-x-2">
      <FormField v-model="localValue.number" class="flex-1" type="tel" :error="errors[`${errorPrefix}number`]" required>
        {{ $t('Phone number') }}
      </FormField>

      <FormField v-model="localValue.extension" type="tel" class="w-20" :error="errors[`${errorPrefix}extension`]">
        {{ $t('Extension') }}
      </FormField>
    </div>

    <FormField :error="errors[`${errorPrefix}type`]" required>
      {{ $t('Type') }}
      <template #component="{ hasError }">
        <AppSelect v-model="localValue.type" :has-error="hasError">
          <option value="mobile">{{ $t('Mobile') }}</option>
          <option value="home">{{ $t('Home') }}</option>
          <option value="work">{{ $t('Work') }}</option>
        </AppSelect>
      </template>
    </FormField>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useVModel } from '@vueuse/core'
import fetchesCountries from '@/composition/fetchesCountries.js'
import AppCombobox from '@/components/forms/AppCombobox.vue'
import FormField from '@/components/forms/FormField.vue'

const props = defineProps({
  modelValue: Object,
  errors: Object,
  errorPrefix: {
    type: String,
    default: ''
  }
})
const emit = defineEmits(['update:modelValue'])
const localValue = useVModel(props, 'modelValue', emit)
const query = ref('')
const country = ref({})
const { countries, fetchCountries } = fetchesCountries()
const displayValue = item => {
  return item?.id
    ? `${item.name} (${item.calling_code})`
    : ''
}
const filteredCountries = computed(() => {
  if (!query.value) {
    return countries.value
  }
  const q = query.value.toLowerCase()

  return countries.value.filter(c => {
    return c.name.toLowerCase().includes(q) ||
      c.calling_code.includes(q)
  })
})
fetchCountries({ phone: true }).then(() => {
  if (localValue.value?.country_id) {
    country.value = countries.value.find(c => c.id === localValue.value.country_id)
  }
})

watch(country, value => {
  localValue.value.country_id = value?.id
})
</script>
