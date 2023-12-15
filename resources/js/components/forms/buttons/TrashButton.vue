<template>
  <button class="inline-flex items-center justify-center text-red-600 rounded-full p-1 hover:bg-red-100 border border-transparent focus:border-red-600 transition">
    <TrashIcon :class="iconSize" />
  </button>
</template>

<script>
import { computed, defineComponent } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    TrashIcon,
  },
  props: {
    size: {
      type: String,
      default: 'sm'
    },
  },

  setup (props, context) {
    const sizes = {
      sm: 'w-4 h-4',
    }

    return {
      iconSize: computed(() => sizes[props.size] || sizes.sm)
    }
  },
})
</script>
