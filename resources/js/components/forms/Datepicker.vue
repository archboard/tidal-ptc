<template>
  <DatePicker
    v-model="localValue"
    v-bind="$attrs"
  />
</template>

<script>
import { defineComponent, ref } from 'vue'
import ModelValue from '@/mixins/ModelValue'
import { DatePicker } from 'v-calendar'
import 'v-calendar/dist/style.css'

export default defineComponent({
  mixins: [ModelValue],
  components: {
    DatePicker,
  },
})
</script>
