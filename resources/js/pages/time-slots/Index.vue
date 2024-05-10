<template>
  <Authenticated>
    <template #actions>
      <AppButton size="sm" component="InertiaLink" href="/time-slots/create">{{ __('Manage') }}</AppButton>
    </template>
    {{ eventSources }}
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import AppButton from '@/components/AppButton.vue'

const props = defineProps({
  eventSources: Array,
})
const emit = defineEmits([])
</script>
