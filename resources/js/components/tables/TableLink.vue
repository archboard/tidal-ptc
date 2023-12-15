<template>
  <component :is="is" class="transition font-medium text-gray-900 dark:text-gray-100 font-medium hover:underline focus:outline-none">
    <slot />
  </component>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    is: {
      type: String,
      default: 'inertia-link',
    }
  }
})
</script>
