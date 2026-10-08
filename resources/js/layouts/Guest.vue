<template>
  <!-- wrap-reverse puts the lava panel on top once the columns stack on phones -->
  <div class="flex min-h-screen flex-wrap-reverse">
    <div class="flex min-w-0 flex-[1_1_420px] flex-col justify-center px-6 py-10 sm:px-12">
      <div class="mx-auto w-full max-w-sm">
        <Alert v-if="status" level="success" class="mb-4">
          {{ status }}
        </Alert>

        <slot />
      </div>
    </div>

    <LavaLamp
      class="m-4 min-h-80 flex-[1_1_520px] rounded-3xl bg-primary-950"
      :colors="['bg-primary-400', 'bg-cyan-300', '#047481', 'bg-sky-500', 'bg-emerald-400']"
      :mask="logoMask"
    />

    <Notifications />
  </div>
</template>

<script setup>
import Alert from '@/components/alerts/SimpleAlert.vue'
import LavaLamp from '@/components/LavaLamp.vue'
import Notifications from '@/components/Notifications.vue'
import logoMask from '@/components/icons/logo-mask.svg'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const status = computed(() => {
  return usePage().props.status
})
</script>
