<template>
  <Authenticated>
    <div>
      <h1 class="font-bold text-2xl leading-5 mb-8">Laravel Inertia Starter</h1>
      <p class="text-lg mb-4">A Laravel starter with Inertia and some components to get you started.</p>

      <AppLink href="/settings">Settings</AppLink>

      <h2 class="text-xl font-bold mb-2">Notifications</h2>
      <div class="space-y-2">
        <div>
          <AppButton @click="notify('This is a success!', 'success')" color="green">Success</AppButton>
        </div>
        <div>
          <AppButton @click.prevent="notify('This is an error!', 'error')" color="red">Error</AppButton>
        </div>
        <div>
          <AppButton @click.prevent="notify('This is a warning!', 'warning')" color="yellow">Warning</AppButton>
        </div>
        <div>
          <AppButton @click.prevent="notify('This is neutral!')" color="neutral">Neutral</AppButton>
        </div>
      </div>

      <h2 class="text-xl font-bold mb-2 mt-10">Alerts</h2>

      <div class="space-y-4">
        <SimpleAlert>My neutral alert.</SimpleAlert>
        <SimpleAlert level="success">My success alert.</SimpleAlert>
        <SimpleAlert level="warning">My warning alert.</SimpleAlert>
        <SimpleAlert level="error">My error alert.</SimpleAlert>
      </div>

      <h2 class="text-xl font-bold mb-2 mt-10">Modal</h2>

      <div class="space-y-2">
        <div>
          <AppButton @click.prevent="showModal = true">Launch Modal</AppButton>
        </div>
        <div>
          <ConfirmButton @confirm="confirmed" color="red">
            Delete?

            <template v-slot:actionText>
              I'm not scared
            </template>
          </ConfirmButton>
        </div>
      </div>


      <Modal
        v-if="showModal"
        @close="showModal = false"
        @action="modalAction"
        :auto-close="false"
        size="sm"
        action-text="Got it!"
        headline="Optional Headline"
      >
        <p>
          This is some default text in the modal, but you could put anything in here like forms or something else.
        </p>
      </Modal>

      <Notifications />
    </div>
  </Authenticated>
</template>

<script>
import { defineComponent } from 'vue'
import Notifications from '@/components/Notifications.vue'
import authCheck from '@/mixins/AuthCheck'
import AppButton from '@/components/AppButton.vue'
import Modal from '@/components/modals/Modal.vue'
import AppLink from '@/components/AppLink.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import ConfirmButton from '@/components/ConfirmButton.vue'
import Authenticated from '@/layouts/Authenticated.vue'

export default defineComponent({
  components: {
    Authenticated,
    ConfirmButton,
    SimpleAlert,
    AppLink,
    Modal,
    AppButton,
    Notifications,
  },

  setup () {
    authCheck()
  },

  data () {
    return {
      showModal: false,
      showDoubleModal: false,
    }
  },

  methods: {
    notify (text, level) {
      this.$notify({
        text,
        level,
      })
    },

    modalAction (closeModal) {
      console.log('Modal action.')

      closeModal()
    },

    confirmed (closeModal) {
      console.log('Action confirmed.')

      closeModal()
    },

    nestedConfirm (closeModal) {
      console.log('Nested action confirmed.')

      closeModal()
      this.$refs.doubleModal.close()
      this.$success('Double modal complete!')
    },
  }
})
</script>
