<template>
  <Menu as="div" class="relative inline-block text-left z-10" v-slot="{ open }">
    <div v-if="open" class="fixed inset-0 opacity-0"></div>
    <Float
      placement="bottom-end"
      enter="transition ease-out duration-100"
      enter-from="transform opacity-0 scale-95"
      enter-to="transform opacity-100 scale-100"
      leave="transition ease-in duration-75"
      leave-from="transform opacity-100 scale-100"
      leave-to="transform opacity-0 scale-95"
      portal
    >
      <MenuButton as="button" class="focus:ring-brand-primary rounded-full inline-flex items-center justify-center p-1">
        <div class="sr-only">Open menu</div>
        <slot name="icon">
          <EllipsisVerticalIcon :class="['text-black dark:text-white', iconSize]" />
        </slot>
      </MenuButton>

      <AppMenuItems>
        <slot>
          <div class="p-1">
            <AppMenuItem
              v-for="item in menuItems"
              is="InertiaLink"
              :href="item.route"
            >
              {{ item.label }}
            </AppMenuItem>
          </div>
        </slot>
      </AppMenuItems>
    </Float>
  </Menu>
</template>

<script setup>
import { Menu, MenuButton } from '@headlessui/vue'
import { Float } from '@headlessui-float/vue'
import AppMenuItem from '@/components/AppMenuItem.vue'
import AppMenuItems from '@/components/AppMenuItems.vue'
import { computed } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  menuItems: {
    type: Array,
  },
  size: {
    type: String,
    default: 'sm',
  }
})
const iconSizes = {
  sm: 'h-4 w-4',
  base: 'h-5 w-5',
}
const iconSize = computed(() => iconSizes[props.size] || iconSizes.base)
</script>
