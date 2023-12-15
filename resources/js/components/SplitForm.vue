<template>
  <div class="space-y-4">
    <CardWrapper>
      <CardPadding>
        <div class="lg:grid lg:grid-cols-3 lg:gap-6">
          <div class="lg:col-span-1">
            <slot name="headline" />
          </div>
          <div class="mt-5 lg:mt-0 lg:col-span-2">
            <div class="grid grid-cols-6 gap-6">
              <slot />
            </div>
          </div>
        </div>
      </CardPadding>
      <CardAction :loading="loading">
        <slot name="actions" :loading="loading" />
      </CardAction>
    </CardWrapper>
  </div>
</template>

<script setup>
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import FormActions from '@/components/forms/FormActions.vue'
import AppButton from '@/components/AppButton.vue'
import CardAction from '@/components/CardAction.vue'

const props = defineProps({
  cancel: String,
  loading: Boolean,
  hideActions: {
    type: Boolean,
    default: () => false,
  }
})
</script>
