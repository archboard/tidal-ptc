<template>
  <div v-if="!ignoreView" class="p-1">
    <AppMenuItem :href="`/students/${student.id}`">{{ __('View') }}</AppMenuItem>
  </div>
  <div class="p-1">
    <AppMenuItem
      as="button"
      method="put"
      href="/toggle-hidden"
      :data="{ model: 'student', id: student.id }"
    >
      {{ student.can_book ? __('Disable booking') : __('Enable booking') }}
    </AppMenuItem>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppMenuItem from '@/components/AppMenuItem.vue'

const props = defineProps({
  student: Object,
  ignoreView: {
    type: Boolean,
    default: false,
  }
})
</script>
