<template>
  <div>
    <AppLabel>{{ $t('Addresses') }}</AppLabel>
    <div class="space-y-4 divide-y divide-gray-300">
      <FadeInGroup>
        <div v-for="(address, index) in localValue" :key="address.id" class="pt-4 space-y-6">
          <Address
            v-model="localValue[index]"
            :errors="errors"
            :error-prefix="`addresses.${index}.`"
          />
          <div class="flex justify-end">
            <AppButton @click.prevent="localValue.splice(index, 1)" size="sm">{{ $t('Remove address') }}</AppButton>
          </div>
        </div>
      </FadeInGroup>
      <div v-if="localValue.length < 2" class="pt-4">
        <AppButton @click.prevent="addAddress" size="sm">{{ $t('Add address')}}</AppButton>
      </div>
    </div>
    <FieldError v-if="errors.addresses" class="mt-3">
      {{ errors.addresses }}
    </FieldError>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { useVModel } from '@vueuse/core'
import AppLabel from '@/components/forms/AppLabel.vue'
import { nanoid } from 'nanoid'
import AppButton from '@/components/AppButton.vue'
import Address from '@/components/forms/Address.vue'
import FormField from '@/components/forms/FormField.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import FadeInGroup from '@/components/transitions/FadeInGroup.vue'
import FieldError from '@/components/forms/FieldError.vue'


export default defineComponent({
  components: {
    FieldError,
    AppButton,
    AppLabel,
    AppSelect,
    FadeInGroup,
    FormField,
    Address,
  },
  props: {
    modelValue: {
      type: Array,
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:modelValue'],

  setup (props, { emit }) {
    const localValue = useVModel(props, 'modelValue', emit)
    const addAddress = () => {
      localValue.value.push({
        id: `new_${nanoid(4)}`,
        street_line_1: null,
        street_line_2: null,
        city: null,
        district: null,
        state: null,
        country_id: null,
        postal_code: null,
        type: null,
      })
    }

    return {
      localValue,
      addAddress,
    }
  }
})
</script>
