<template>
  <div class="p-1">
    <AppMenuItem v-if="can('user.view')" :href="`/users/${user.id}`">
      {{ __('View') }}
    </AppMenuItem>
    <AppMenuItem v-if="can('time_slot.create')" :href="`/users/${user.id}/edit`">
      {{ __('Edit time slots') }}
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
  <div v-if="can(`${user.model_alias}.update`)" class="p-1">
    <AppMenuItem as="button" method="post" :href="`/sync/${user.model_alias}/${user.id}`">
      <span class="flex items-center gap-2">
        <ArrowPathIcon class="h-5 w-5" />
        <span>{{ __('Sync from SIS') }}</span>
      </span>
    </AppMenuItem>
  </div>
</template>

<script setup>
import AppMenuItem from '@/components/AppMenuItem.vue'
import { ArrowPathIcon } from '@heroicons/vue/24/outline/index.js'

const props = defineProps({
  user: Object,
})
</script>
