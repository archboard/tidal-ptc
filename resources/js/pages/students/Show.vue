<template>
  <Authenticated>
    <pre>{{ student }}</pre>
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'

const props = defineProps({
  student: Object,
})
</script>
