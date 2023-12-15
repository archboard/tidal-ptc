<template>
  <ModalWrapper :show="show" @close="close">
    <DropIn @after-leave="$emit('close')">
      <div v-if="show" ref="modal" :class="modalSize" class="w-full inline-block align-middle bg-white dark:bg-gray-600 rounded-2xl text-left shadow-xl transform transition-all sm:my-8" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
        <div class="hidden sm:block absolute top-0 right-0 pt-4 pr-3">
          <button @click.prevent="close" type="button" class="bg-white dark:bg-gray-600 rounded-full text-gray-400 hover:text-gray-500 focus:bg-gray-50 focus:outline-none focus:ring focus:ring-gray-300 dark:focus:ring-gray-500 transition ease-in-out">
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="sm:flex sm:items-start px-4 pt-5 pb-4 sm:p-6">
          <div class="mt-3 sm:mt-0 w-full">
            <ModalHeadline v-if="headline">
              {{ headline }}
            </ModalHeadline>
            <div>
              <slot/>
            </div>
          </div>
        </div>
        <div v-if="!hideActions" class="bg-gray-100 dark:bg-gray-700 px-4 py-3 sm:px-6 flex flex-col space-y-2 sm:space-y-0 sm:flex-row-reverse rounded-b-2xl">
          <slot name="actions" :close="close">
            <AppButton @click.prevent="performAction" :loading="actionLoading" type="button" :color="actionColor" class="sm:ml-2 text-sm">
              {{ computedActionText }}
            </AppButton>
            <AppButton @click.prevent="close" type="button" color="white" class="text-sm">
              {{ __('Close') }}
            </AppButton>
          </slot>
        </div>
      </div>
    </DropIn>
  </ModalWrapper>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import ModalHeadline from '@/components/modals/ModalHeadline.vue'
import ModalWrapper from '@/components/modals/ModalWrapper.vue'
import DropIn from '@/components/transitions/DropIn.vue'
import { trans } from 'laravel-vue-i18n'
import AppButton from '@/components/AppButton.vue'

const props = defineProps({
  headline: String,
  actionText: String,
  actionLoading: {
    type: Boolean,
    default: false
  },
  actionColor: {
    type: String,
    default: 'primary',
  },
  autoClose: {
    type: Boolean,
    default: () => false
  },
  size: {
    type: String,
    default: 'lg'
  },
  hideActions: {
    type: Boolean,
    default: false,
  }
})
const emit = defineEmits(['close', 'action'])
const show = ref(false)
const close = () => {
  show.value = false
}
const modal = ref()
const performAction = () => {
  emit('action', close)

  if (props.autoClose) {
    close()
  }
}
const listener = (e) => {
  if (e.key === 'Escape') {
    e.stopPropagation()
    close()
  }
}
const computedActionText = computed(() => {
  return props.actionText || trans('Save')
})
const modalSize = computed(() => {
  const modalSizes = {
    xs: 'sm:max-w-xs',
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
  }

  return modalSizes[props.size]
})

onMounted(() => {
  show.value = true
  document.removeEventListener('keydown', listener)

  nextTick(() => {
    document.body.classList.add('no-scroll')
  })
})

onUnmounted(() => {
  document.body.classList.remove('no-scroll')
  document.addEventListener('keydown', listener)
})

defineExpose({ close })
</script>
