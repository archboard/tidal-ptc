<template>
  <div v-if="!ignoreView" class="p-1">
    <AppMenuItem :href="`/sections/${section.id}`">{{ __('View') }}</AppMenuItem>
  </div>
  <div class="p-1">
    <AppMenuItem
      as="button"
      method="put"
      href="/toggle-hidden"
      :data="{ model: 'section', id: section.id }"
    >
      {{ section.can_book ? __('Disable booking') : __('Enable booking') }}
    </AppMenuItem>
  </div>
</template>

<script setup>
import AppMenuItem from '@/components/AppMenuItem.vue'

const props = defineProps({
  section: Object,
  ignoreView: {
    type: Boolean,
    default: false,
  }
})
</script>
