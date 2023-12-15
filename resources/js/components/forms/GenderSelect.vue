<template>
  <AppSelect v-model="localValue">
    <option value="male">{{ $t('Male') }}</option>
    <option value="female">{{ $t('Female') }}</option>
    <option value="not specified">{{ $t('Not specified') }}</option>
  </AppSelect>
</template>

<script>
import { defineComponent, ref } from 'vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import { useVModel } from '@vueuse/core'

export default defineComponent({
  components: {
    AppSelect,
  },
  props: {
    nullable: {
      type: Boolean,
      default: () => false,
    }
  },
  emits: ['update:modelValue'],

  setup (props, { emit }) {
    const localValue = useVModel(props, 'modelValue', emit)

    return {
      localValue,
    }
  }
})
</script>
