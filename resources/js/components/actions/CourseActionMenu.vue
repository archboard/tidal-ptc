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
</template>

<script setup>
import AppMenuItem from '@/components/AppMenuItem.vue'

const props = defineProps({
  course: Object,
  ignoreView: {
    type: Boolean,
    default: false,
  }
})
</script>
