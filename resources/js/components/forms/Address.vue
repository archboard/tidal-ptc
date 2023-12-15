<template>
  <div class="space-y-4">
    <FormField
      v-model="localValue.street_line_1"
      :error="errors[`${errorPrefix}street_line_1`]"
      required
    >
      {{ $t('Street address') }}
    </FormField>

    <div class="flex justify-between items-start space-x-2">
      <FormField
        v-model="localValue.street_line_2"
        :error="errors[`${errorPrefix}second_line`]"
        class="w-full"
      >
        {{ $t('Street address line 2')}}
      </FormField>

      <FormField
        :error="errors[`${errorPrefix}type`]"
        class="w-full"
        required
      >
        {{ $t('Type') }}
        <template #component>
          <AppSelect v-model="localValue.type">
            <option value="home">{{ $t('Home') }}</option>
            <option value="work">{{ $t('Work') }}</option>
          </AppSelect>
        </template>
      </FormField>
    </div>

    <div class="flex justify-between items-start space-x-2">
      <FormField
        v-model="localValue.city"
        class="w-full"
        :error="errors[`${errorPrefix}city`]"
        required
      >
        {{ $t('City') }}
      </FormField>

      <FormField
        v-model="localValue.state"
        class="w-3/5"
        :error="errors[`${errorPrefix}state`]"
      >
        {{ $t('State/Province') }}
      </FormField>

    </div>

    <div class="flex justify-between items-start space-x-2">
      <FormField
        v-model="localValue.district"
        class="w-full"
        :error="errors[`${errorPrefix}district`]"
      >
        {{ $t('District') }}
      </FormField>

      <FormField
        v-model="localValue.postal_code"
        class="w-full"
        :error="errors[`${errorPrefix}postal_code`]"
      >
        {{ $t('Zip/Postal Code') }}
      </FormField>

    </div>

    <FormField :error="errors[`${errorPrefix}country_id`]" required>
      {{ $t('Country')}}
      <template #component="{ hasError }">
        <CountrySelect v-model="localValue.country_id" :has-error="hasError" />
      </template>
    </FormField>
  </div>
</template>

<script setup>
import { useVModel } from '@vueuse/core'
import FormField from '@/components/forms/FormField.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import CountrySelect from '@/components/forms/CountrySelect.vue'

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
</script>
