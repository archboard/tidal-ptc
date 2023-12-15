<template>
  <div class="flex items-center">
    <input :id="id" :name="name" type="radio" v-model="localValue" :value="value" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300">
    <label :for="id" class="ml-3 block text-sm font-medium text-gray-700">
      <slot />
    </label>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import ModelValue from '@/mixins/ModelValue'
import { nanoid } from 'nanoid'

export default defineComponent({
  mixins: [ModelValue],
  props: {
    value: [String, Number, Boolean],
    name: String,
  },

  setup () {
    return {
      id: nanoid()
    }
  }
})
</script>
