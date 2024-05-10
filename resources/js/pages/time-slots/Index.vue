<template>
  <Authenticated>
    {{ eventSources }}
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'

const props = defineProps({
  eventSources: Array,
})
const emit = defineEmits([])
</script>
