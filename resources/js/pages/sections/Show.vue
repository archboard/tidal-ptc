<template>
  <Authenticated></Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'

const props = defineProps({})
const emit = defineEmits([])
</script>
