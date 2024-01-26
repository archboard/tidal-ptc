<template>
  <div
    class="bg-gray-50 dark:bg-gray-700 rounded-br-2xl rounded-bl-2xl"
    :class="{
      '-mx-4 -mb-5 mt-6 sm:-mx-6': negativeMargin
    }"
  >
    <CardPadding class="space-x-2 flex justify-end">
      <slot>
        <AppButton type="submit" :loading="loading">
          {{ __('Save') }}
        </AppButton>
      </slot>
    </CardPadding>
  </div>
</template>

<script setup>
import CardPadding from '@/components/CardPadding.vue'
import AppButton from '@/components/AppButton.vue'
const props = defineProps({
  negativeMargin: {
    type: Boolean,
    default: false
  },
  loading: Boolean,
})
</script>
