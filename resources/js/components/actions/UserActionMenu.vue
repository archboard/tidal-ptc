<template>
  <div class="p-1">
    <AppMenuItem v-if="can('user.view')" :href="`/users/${user.id}`">
      {{ __('View') }}
    </AppMenuItem>
  </div>
  <div class="p-1">
    <AppMenuItem
      as="button"
      method="put"
      href="/toggle-hidden"
      :data="{ model: 'user', id: user.id }"
    >
      {{ user.can_book ? __('Disable booking') : __('Enable booking') }}
    </AppMenuItem>
    <AppMenuItem v-if="can('edit_permissions')" :href="`/users/${user.id}/permissions`">
      {{ __('Edit permissions') }}
    </AppMenuItem>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppMenuItem from '@/components/AppMenuItem.vue'

const props = defineProps({
  user: Object,
})
const emit = defineEmits([])
</script>
