<template>
  <MenuItem v-slot="{ active }">
    <component
      :is="is"
      :class="[
        active ? classes.active : classes.inactive,
        classes.always,
      ]"
      v-bind="$attrs"
    >
      <slot :active="active" />
    </component>
  </MenuItem>
</template>

<script>
import { defineComponent } from 'vue'
import { MenuItem } from '@headlessui/vue'
import menuItemClasses from '@/composition/useMenuItemClasses.js'

export default defineComponent({
  inheritAttrs: false,
  components: {
    MenuItem,
  },

  props: {
    is: {
      type: String,
      default: 'button',
    }
  },

  setup () {
    const classes = menuItemClasses()

    return {
      classes,
    }
  }
})
</script>
