<template>
  <AppButton @click.prevent="$emit('removed')" size="sm" color="gray_light">
    <ion-icon name="trash-bin" class="w-4 h-4"></ion-icon>
    <span>
      {{ $t('Remove') }}
    </span>
  </AppButton>
</template>

<script setup>
import AppButton from '@/components/AppButton.vue'

const emit = defineEmits(['removed'])
</script>
