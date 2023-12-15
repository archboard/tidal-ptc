<template>
  <div class="min-h-screen flex flex-col sm:justify-center pt-48 sm:pt-0 items-center">
    <div class="max-w-sm px-4 w-full relative">
      <div class="pb-5 absolute top-0 inset-x-0 flex justify-center transform -translate-y-full">
        <InertiaLink href="/">
<!--          <Logo class="w-20 h-20 fill-current text-white" />-->
        </InertiaLink>
      </div>

      <Alert v-if="status" level="success" class="mb-4">
        {{ status }}
      </Alert>

      <slot />
    </div>

    <Notifications />
  </div>
</template>

<script setup>
import Alert from '@/components/alerts/SimpleAlert.vue'
import Notifications from '@/components/Notifications.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const status = computed(() => {
  return usePage().props.status
})
</script>
