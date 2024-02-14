<template>
  <div v-if="!ignoreView" class="p-1">
    <AppMenuItem :href="`/courses/${course.id}`">{{ __('View') }}</AppMenuItem>
  </div>
  <div class="p-1">
    <AppMenuItem
      as="button"
      method="put"
      href="/toggle-hidden"
      :data="{ model: 'course', id: course.id }"
    >
      {{ course.can_book ? __('Disable booking') : __('Enable booking') }}
    </AppMenuItem>
  </div>
  <div v-if="can(`${course.model_alias}.update`)" class="p-1">
    <AppMenuItem as="button" method="post" :href="`/sync/${course.model_alias}/${course.id}`">
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
  course: Object,
  ignoreView: {
    type: Boolean,
    default: false,
  }
})
</script>
