<template>
  <div class="rounded-lg shadow p-4" :class="bgColor">
    <div class="flex">
      <div class="flex-shrink-0">
        <component :is="icon" class="h-5 w-5" :class="iconColor" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 v-if="header" class="mb-2 text-sm font-medium" :class="textColor">
          {{ header }}
        </h3>
        <div class="text-sm" :class="textColor">
          <p>
            <slot />
          </p>
        </div>
        <div v-if="actionText" class="mt-4">
          <div class="-mx-2 -my-1.5 flex">
            <button @click.prevent="$emit('action')" type="button" class="px-2 py-1.5 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2" :class="dismissColor">
              {{ actionText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import alertColors from '@/composition/alertColors'
import AlertComponent from '@/mixins/AlertComponent'

export default defineComponent({
  mixins: [AlertComponent],
  props: {
    header: String,
    actionText: String,
  },
  emits: ['action'],

  setup ({ level }) {
    const colors = alertColors(level)

    return {
      ...colors,
    }
  },
})
</script>
