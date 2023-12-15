<template>
  <component :is="is" :class="`font-medium font-sans cursor-pointer focus:outline-none transition duration-100 ease-in-out ${color}`">
    <slot />
  </component>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    is: {
      type: String,
      default: 'InertiaLink',
    },
    color: {
      type: String,
      default: 'primary',
    }
  },

  setup (props) {
    const colorClasses = {
      primary: 'text-primary-500 dark:text-primary-300 hover:underline',
    }

    return {
      color: colorClasses[props.color] || colorClasses.primary
    }
  }
})
</script>
