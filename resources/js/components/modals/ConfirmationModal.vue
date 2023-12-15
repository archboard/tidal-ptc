<template>
  <Modal
    @close="$emit('close')"
    ref="modal"
    size="sm"
  >
    <div>
      <slot name="icon">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
          <ExclamationTriangleIcon class="h-6 w-6 text-yellow-600" />
        </div>
      </slot>

      <div class="mt-3 text-center sm:mt-5 w-full">
        <h3 class="text-lg leading-6" id="modal-headline">
          <slot name="headline">
            {{ __('Are you sure?') }}
          </slot>
        </h3>
        <div class="mt-2">
          <p class="text-sm">
            <slot>
              {{ __('This action cannot be undone.') }}
            </slot>
          </p>
        </div>
      </div>
    </div>

    <template v-slot:actions>
      <div class="space-y-2 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense w-full">
        <AppButton type="button" @click.prevent="confirmed" :loading="loading" class="w-full" color="yellow" ref="action">
          <slot name="actionText">
            {{ __("I'm sure") }}
          </slot>
        </AppButton>
        <AppButton type="button" color="white" @click.prevent="modal.close" class="w-full">
          {{ __('Never mind') }}
        </AppButton>
      </div>
    </template>
  </Modal>
</template>

<script>
import { defineComponent, nextTick, onMounted, ref } from 'vue'
import Modal from '@/components/modals/Modal.vue'
import AppButton from '@/components/AppButton.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    AppButton,
    Modal,
    ExclamationTriangleIcon,
  },
  emits: ['close', 'confirmed'],
  props: {
    loading: Boolean,
  },

  setup (props, { emit }) {
    const action = ref(null)
    const modal = ref(null)
    const confirmed = () => {
      emit('confirmed', () => {
        modal.value.close()
      })
    }

    onMounted(() => {
      nextTick(() => {
        action.value?.$el?.focus()
      })
    })

    return {
      modal,
      action,
      confirmed,
    }
  }
})
</script>
