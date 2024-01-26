<template>
  <FadeIn>
    <div v-if="show" class="rounded-lg shadow p-4" :class="bgColor">
      <div class="flex">
        <div class="flex flex-shrink-0">
          <component :is="icon" class="h-5 w-5" :class="iconColor" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium" :class="textColor">
            <slot />
          </p>
        </div>
        <div class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button @click.prevent="show = false" type="button" class="inline-flex rounded-lg p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" :class="dismissColor">
              <span class="sr-only">Dismiss</span>
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </FadeIn>
</template>

<script>
import { ref } from 'vue'
import FadeIn from '@/components/transitions/FadeIn.vue'
import alertColors from '@/composition/alertColors.js'
import AlertComponent from '@/mixins/AlertComponent.js'
import { XMarkIcon } from '@heroicons/vue/24/solid'

export default {
  mixins: [AlertComponent],
  components: {
    XMarkIcon,
    FadeIn,
  },

  setup (props) {
    const colors = alertColors(props.level)
    const show = ref(true)

    return {
      ...colors,
      show,
    }
  }
}
</script>
