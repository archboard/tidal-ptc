<template>
  <FormField :error="error" :help="helpText" :required="required">
    {{ label }}
    <template #component>
      <UserSearch
        v-model="localObject"
        v-model:user-id="localValue"
        :has-error="!!error"
        :disabled="disabled"
      />
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'
import { inject, ref } from 'vue'
import UserSearch from '@/components/forms/UserSearch.vue'

const $http = inject('$http')
const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
const localObject = ref({})

if (props.modelValue) {
  $http.get(`/search/users`, {
    params: { id: props.modelValue }
  }).then(({ data }) => {
    localObject.value = data[0] || {}
  })
}
</script>
