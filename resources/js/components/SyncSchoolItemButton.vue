<template>
  <AppButton v-if="can('edit_school_settings')" size="sm" :loading="syncing" @click.prevent="sync()">
    <slot>{{ __('Sync from SIS') }}</slot>
  </AppButton>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppButton from '@/components/AppButton.vue'

const props = defineProps({
  item: {
    type: String,
    required: true,
  },
})
const syncing = ref(false)
const sync = () => {
  syncing.value = true
  router.post(`/settings/school/sync/${props.item}`, null, {
    preserveScroll: true,
    onFinish: () => {
      syncing.value = false
    },
  })
}
</script>
