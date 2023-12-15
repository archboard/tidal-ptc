<template>
  <transition
    leave-active-class="transition-transform duration-150"
    leave-from-class="translate-y-0"
    leave-to-class="translate-y-full"
  >
    <div v-if="show" class="fixed inset-x-0 bottom-0">
      <div class="bg-yellow-600">
        <div class="max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between flex-wrap">
            <div class="w-0 flex-1 flex items-center">
              <span class="flex p-2 rounded-lg bg-yellow-800">
                <ClockIcon class="h-6 w-6 text-white" aria-hidden="true" />
              </span>
              <p class="ml-3 font-medium text-white truncate">
                <span>{{ __("Are you in the :timezone timezone?", { timezone: timezone.replace('_', ' ') }) }}</span>
              </p>
            </div>
            <div class="order-3 mt-2 flex-shrink-0 w-full sm:order-2 sm:mt-0 sm:w-auto">
              <button @click.prevent="showModal = true" class="flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-yellow-600 bg-white hover:bg-yellow-50">
                {{ __('No, change it') }}
              </button>
            </div>
            <div class="order-2 flex-shrink-0 sm:order-3 sm:ml-3">
              <button @click.prevent="manualShow = false" type="button" class="-mr-1 flex p-2 rounded-md hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-white sm:-mr-2">
                <span class="sr-only">Dismiss</span>
                <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>

  <TimezoneModal
    v-if="showModal"
    @close="showModal = false"
  />
</template>

<script setup>
import { ClockIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'
import useDates from '@/composition/useDates.js'
import TimezoneModal from '@/components/modals/TimezoneModal.vue'
import { useLocalStorage } from '@vueuse/core'

const { timezone, dayjs } = useDates()
const emit = defineEmits(['launch'])
const manualShow = ref(true)
const ignore = useLocalStorage('ignore-timezone-prompt', false)
const dateString = '1988-12-08 14:00:00'
const guessMatchesCurrent = computed(() => {
  const current = dayjs(dateString).tz(timezone.value)
  const guessed = dayjs(dateString).tz(dayjs.tz.guess())

  return current.format('YY-MM-DD HH:mm:ss') ===
    guessed.format('YY-MM-DD HH:mm:ss')
})
const show = computed(() => {
  return dayjs.tz.guess() &&
    manualShow.value &&
    !guessMatchesCurrent.value
})
const showModal = ref(!ignore.value && dayjs.tz.guess() && !guessMatchesCurrent.value)
</script>
