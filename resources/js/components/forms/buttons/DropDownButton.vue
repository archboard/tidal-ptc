<template>
  <Menu as="div" class="relative inline-block text-left z-10">
    <div>
      <MenuButton class="focus:outline-none">
        <AppButton :size="size">
          <slot />
          <ChevronDownIcon class="-mr-1 ml-2" :class="iconSize" aria-hidden="true" />
        </AppButton>
      </MenuButton>
    </div>

    <ScaleIn>
      <AppMenuItems>
        <slot name="dropdown">
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
    </ScaleIn>
  </Menu>
</template>

<script>
import { Menu, MenuButton } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'
import ScaleIn from '@/components/transitions/ScaleIn.vue'
import AppButton from '@/components/AppButton.vue'
import AppMenuItem from '@/components/AppMenuItem.vue'
import AppMenuItems from '@/components/AppMenuItems.vue'

export default {
  components: {
    AppButton,
    ScaleIn,
    AppMenuItem,
    AppMenuItems,
    Menu,
    MenuButton,
    ChevronDownIcon,
  },

  props: {
    menuItems: Array,
    size: {
      type: String,
      default: 'sm',
    }
  },

  setup ({ size }) {
    const iconSizes = {
      sm: 'h-4 w-4',
      base: 'h-5 w-5',
    }

    return {
      iconSize: iconSizes[size] || iconSizes.base,
    }
  }
}
</script>
