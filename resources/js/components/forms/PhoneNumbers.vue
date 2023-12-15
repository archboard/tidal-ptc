<template>
  <div>
    <AppLabel>{{ $t('Phone numbers') }}</AppLabel>
    <div class="space-y-4 divide-y divide-gray-300">
      <FadeInGroup>
        <div v-for="(phone, index) in localValue" :key="phone.id" class="pt-4 space-y-4">
          <div class="flex justify-between items-start">
            <span class="font-medium">{{ $t('Phone :list_number', { list_number: index + 1 }) }}</span>
            <RemoveButton @removed="localValue.splice(index, 1)" />
          </div>
          <PhoneNumber
            v-model="localValue[index]"
            :errors="errors"
            :error-prefix="`phones.${index}.`"
          />
        </div>
      </FadeInGroup>
      <div class="pt-4 text-right">
        <AppButton @click.prevent="addNumber" size="sm">{{ $t('Add phone number') }}</AppButton>
      </div>
    </div>
    <FieldError v-if="errors.phones" class="mt-3">
      {{ errors.phones }}
    </FieldError>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { useVModel } from '@vueuse/core'
import AppLabel from '@/components/forms/AppLabel.vue'
import { nanoid } from 'nanoid'
import AppButton from '@/components/AppButton.vue'
import PhoneNumber from '@/components/forms/PhoneNumber.vue'
import FormField from '@/components/forms/FormField.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import FadeInGroup from '@/components/transitions/FadeInGroup.vue'
import FieldError from '@/components/forms/FieldError.vue'
import RemoveButton from '@/components/forms/buttons/RemoveButton.vue'

export default defineComponent({
  components: {
    RemoveButton,
    FieldError,
    FadeInGroup,
    AppSelect,
    FormField,
    PhoneNumber,
    AppButton,
    AppLabel,
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
    const addNumber = () => {
      localValue.value.push({
        id: `new_${nanoid(4)}`,
        country_id: null,
        number: null,
        extension: null,
        type: null,
      })
    }

    return {
      localValue,
      addNumber,
    }
  }
})
</script>
