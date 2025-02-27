<template>
  <div v-if="!ignoreView" class="p-1">
    <AppMenuItem :href="`/sections/${section.id}`">{{ __('View') }}</AppMenuItem>
  </div>
  <div v-if="can('section.update')" class="p-1">
    <AppMenuItem :href="`/sections/${section.id}/edit`">{{ __('Edit section') }}</AppMenuItem>
    <AppMenuItem
      v-if="section.alt_user_id"
      as="button"
      method="put"
      :href="`/sections/${section.id}`"
      :data="{ alt_user_id: null }"
    >
      {{ __('Remove teacher override') }}
    </AppMenuItem>
    <AppMenuItem
      as="button"
      method="put"
      href="/toggle-hidden"
      :data="{ model: 'section', id: section.id }"
    >
      {{ section.can_book ? __('Disable section booking') : __('Enable section booking') }}
    </AppMenuItem>
  </div>
  <div v-if="section.course && can('course.update')" class="p-1">
    <AppMenuItem
      as="button"
      method="put"
      href="/toggle-hidden"
      :data="{ model: 'course', id: section.course.id }"
    >
      {{ section.course.can_book ? __('Disable course booking') : __('Enable course booking') }}
    </AppMenuItem>
  </div>
  <div v-if="can('section.update')" class="p-1">
    <AppMenuItem as="button" method="post" :href="`/sync/${section.model_alias}/${section.id}`">
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
  section: Object,
  ignoreView: {
    type: Boolean,
    default: false,
  }
})
</script>
