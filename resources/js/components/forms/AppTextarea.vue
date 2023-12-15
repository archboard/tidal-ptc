<template>
  <textarea
    v-model="localValue"
    @input="resize"
    ref="textarea"
    :style="{ height }"
    class="shadow-sm block w-full focus:ring-2 text-sm rounded-md"
    :class="{
      'focus:ring-brand-blue focus:border-brand-blue border-gray-300': !hasError,
      'pr-10 border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': hasError,
    }"
  ></textarea>
</template>

<script>
import { defineComponent, ref, onMounted, nextTick } from 'vue'
import ModelValue from '@/mixins/ModelValue'

export default defineComponent({
  mixins: [ModelValue],
  props: {
    autofocus: {
      type: Boolean,
      default: false,
    },
    hasError: {
      type: Boolean,
      default: false,
    }
  },

  setup (props) {
    const textarea = ref(null)
    const baseHeight = 66
    const height = ref(`auto`)
    const resize = () => {
      height.value = `auto`

      nextTick(() => {
        const scrollHeight = textarea.value.scrollHeight

        if (scrollHeight > baseHeight) {
          height.value = `${scrollHeight}px`
        }
      })
    }
    onMounted(resize)

    if (props.autofocus) {
      onMounted(() => {
        textarea.value.focus()
      })
    }

    return {
      textarea,
      height,
      resize,
    }
  }
})
</script>
