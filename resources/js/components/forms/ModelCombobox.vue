<template>
  <AppCombobox
    v-model="localValue"
    v-model:query="query"
    :options="options"
    :has-error="hasError"
    :disabled="disabled"
    :id="id"
    :loading="loading"
  />
</template>

<script setup>
import { computed, inject, ref, toValue } from 'vue'
import AppCombobox from '@/components/forms/AppCombobox.vue'
import { debouncedWatch } from '@vueuse/core'
import isEmpty from 'just-is-empty'

const props = defineProps({
  modelValue: [Number, String],
  object: {
    type: Object,
    default: () => ({}),
  },
  model: {
    type: String,
    required: true,
  },
  hasError: {
    type: Boolean,
    default: () => false,
  },
  id: String,
  disabled: {
    type: Boolean,
    default: () => false,
  }
})
const emit = defineEmits([
  'update:modelValue',
  'update:object',
])
const $http = inject('$http')
const localObject = ref({})
const localValue = computed({
  get: () => isEmpty(props.object) ? toValue(localObject) : props.object,
  set: (value) => {
    localObject.value = value || {}
    emit('update:modelValue', value?.id)
    emit('update:object', value)
  }
})
const query = ref()
const options = ref([])
const loading = ref(false)
const search = async (searchString) => {
  loading.value = true

  try {
    const { data } = await $http.post(`/search/${props.model}`, {
      search: toValue(searchString),
    })
    options.value = data
  } catch (err) { }

  loading.value = false
}

if (props.modelValue) {
  search(props.modelValue).then(() => {
    localValue.value = options.value.find(option => option.id === props.modelValue)
  })
}

debouncedWatch(query, () => {
  if (query.value.length > 2) {
    search(query)
  }
}, { debounce: 500 })
</script>
