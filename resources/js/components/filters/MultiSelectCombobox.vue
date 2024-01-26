<template>
  <div class="min-w-[16rem] relative z-10">
    <AppCombobox
      v-model="localItem"
      v-model:query="query"
      :options="filteredOptions"
      :display-value="item => item?.label"
    />
    <div class="mt-2" v-if="selectedOptions.length > 0">
      <div class="-m-1 flex flex-wrap items-center">
        <template v-for="option in selectedOptions" :key="option.value">
          <DismissablePill @removed="removeSelection(option.value)">
            {{ option.label }}
          </DismissablePill>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import AppCombobox from '@/components/forms/AppCombobox.vue'
import DismissablePill from '@/components/DismissablePill.vue'
import useSelectOptions from '@/composition/useSelectOptions.js'

const props = defineProps({
  modelValue: Array,
  options: {
    type: [Array, Object],
    required: true,
  },
  attribute: {
    type: String,
    default: 'id',
  },
  displayUsing: {
    type: [Function, String],
    default: item => item?.name || item?.label || '',
  }
})
const emit = defineEmits(['update:modelValue'])
const localValue = computed({
  get: () => props.modelValue || [],
  set: (value) => {
    emit('update:modelValue', value)
  }
})
const displayOption = item => {
  if (typeof props.displayUsing === 'string') {
    return item?.[props.displayUsing]
  }

  return props.displayUsing(item)
}
const localItem = ref()
const query = ref()
const options = useSelectOptions(props.options)
const filteredOptions = computed(() => {
  if (!query.value || typeof query.value !== 'string') {
    return options.value.filter(o => !localValue.value.includes(o[props.attribute]))
  }

  const q = query.value.toLowerCase()

  return options.value.filter(o => o.label.toLowerCase().includes(q) &&
    !localValue.value.includes(o[props.attribute])
  )
})
const selectedOptions = computed(() => {
  return options.value.filter(o => localValue.value.includes(o.value))
})
const removeSelection = value => {
  const index = localValue.value.findIndex(v => v === value)

  if (index > -1) {
    localValue.value.splice(index, 1)
  }
}

watch(localItem, value => {
  if (value?.value) {
    localValue.value.push(value.value)
    localItem.value = null
    query.value = ''
  }
})
</script>
