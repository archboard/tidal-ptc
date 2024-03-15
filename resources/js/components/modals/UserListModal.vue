<template>
  <Modal @close="$emit('close')">
    <ul class="list-disc pl-6">
      <li v-for="user in users" :key="user.id">{{ user.name }} <span v-if="user.email">({{ user.email }})</span></li>
    </ul>

    <template #actions="{ close }">
      <AppButton @click.prevent="close()" type="button" color="white" class="text-sm">
        {{ __('Close') }}
      </AppButton>
    </template>
  </Modal>
</template>

<script setup>
import Modal from '@/components/modals/Modal.vue'
import AppButton from '@/components/AppButton.vue'
import { ref } from 'vue'

const props = defineProps({
  users: Array,
})
const emit = defineEmits(['close'])
const modal = ref()
</script>
