<template>
  <td
    class="px-6 py-4 text-sm"
    :class="{
      'text-gray-700 dark:text-gray-300': !darker,
      'text-black dark:text-gray-100 font-medium': darker,
    }"
  >
    <slot />
  </td>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    darker: {
      type: Boolean,
      default: () => false,
    }
  }
})
</script>
