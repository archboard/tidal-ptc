<template>
  <div v-if="demo" class="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white">
    {{ __('Demo mode: data resets every hour and settings are read-only.') }}
  </div>
</template>

<script setup>
import useProp from '@/composition/useProp.js'

const demo = useProp('demo', false)
</script>
