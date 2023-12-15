<template>
  <div class="py-12 max-w-lg mx-auto w-full">
    <h2 class="font-bold mb-8">Menu</h2>
    <Menu>
      <MenuButton>Drop down</MenuButton>
      <MenuItems>
        <MenuItem v-slot="{ active }">
          <a :class='{ "bg-primary-500": active }' href="#">
            Account settings
          </a>
        </MenuItem>
        <MenuItem v-slot="{ active }">
          <a :class='{ "bg-primary-500": active }' href="#">
            Documentation
          </a>
        </MenuItem>
        <MenuItem disabled>
          <span class="opacity-75">Invite a friend (coming soon!)</span>
        </MenuItem>
      </MenuItems>
    </Menu>

    <div>
      <AppDatepicker v-model="date" />
      <pre>{{ date }}</pre>
    </div>
  </div>
</template>

<script setup>
import { defineComponent, ref } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import AppDatepicker from '@/components/forms/AppDatepicker.vue'

const date = ref()
</script>
