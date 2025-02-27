<template>
  <span class="inline-flex">
    <Pill v-if="expanded" color="yellow">
      {{ __('Booking disabled') }}
    </Pill>
    <span v-else>
      <LockClosedIcon class="h-4 w-4 text-yellow-500" />
      <span class="sr-only">{{ __('Booking disabled') }}</span>
    </span>
  </span>
</template>

<script setup>
import Pill from '@/components/Pill.vue'
import { LockClosedIcon } from '@heroicons/vue/24/solid/index.js'

const props = defineProps({
  expanded: {
    type: Boolean,
    default: false,
  },
})
</script>
