<template>
  <MenuItems class="origin-top-right absolute z-10 right-0 mt-2 w-56 divide-y divide-gray-100 dark:divide-gray-500 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 focus:outline-none">
    <slot />
  </MenuItems>
</template>

<script>
import { defineComponent } from 'vue'
import { MenuItems } from '@headlessui/vue'

export default defineComponent({
  components: {
    MenuItems,
  },
})
</script>
