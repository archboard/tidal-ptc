<template>
  <div class="inline-block">
    <AppButton v-bind="$attrs" @click.prevent="show = true">
      <slot />
    </AppButton>

    <ConfirmationModal
      v-if="show"
      @confirmed="confirmed"
      @close="show = false"
    >
      <template #actionText>
        <slot name="actionText" />
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppButton from '@/components/AppButton.vue'
import ConfirmationModal from '@/components/modals/ConfirmationModal.vue'

const emit = defineEmits(['confirmed'])
const modal = ref()
const show = ref(false)

const confirmed = (close) => {
  emit('confirmed', close)
}
</script>
