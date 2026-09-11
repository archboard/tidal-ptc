<template>
  <Modal @close="$emit('close')" :headline="activity.description" hide-actions>
    <dl class="text-sm grid grid-cols-3 gap-y-2 mb-4">
      <dt class="text-gray-500 dark:text-gray-300">{{ __('When') }}</dt>
      <dd class="col-span-2">{{ activity.created_at }}</dd>
      <dt class="text-gray-500 dark:text-gray-300">{{ __('Who') }}</dt>
      <dd class="col-span-2">{{ activity.causer ?? __('System') }}</dd>
      <dt class="text-gray-500 dark:text-gray-300">{{ __('Subject') }}</dt>
      <dd class="col-span-2">{{ activity.subject ?? activity.subject_type ?? '—' }}</dd>
    </dl>
    <!-- ponytail: generic dump; per-event renderers come later -->
    <ActivityDetails :activity="activity" />
  </Modal>
</template>

<script setup>
import Modal from '@/components/modals/Modal.vue'
import ActivityDetails from '@/components/ActivityDetails.vue'

defineProps({
  activity: Object,
})
defineEmits(['close'])
</script>
