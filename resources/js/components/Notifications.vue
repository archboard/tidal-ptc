<template>
  <teleport to="body">
    <div class="fixed right-0 top-0 z-50 max-w-sm w-full px-4 space-y-4 max-h-screen scroll-y-scroll">
      <transition-group
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
        move-class="transition-all duration-150 ease-in"
      >
        <notification
          v-for="notification in state.notifications"
          :key="notification.id"
          :notification="notification"
        />
      </transition-group>
    </div>
  </teleport>
</template>

<script>
import { defineComponent } from 'vue'
import store from '@/stores/notifications'
import Notification from '@/components/Notification.vue'

export default defineComponent({
  components: { Notification },

  setup () {
    return {
      state: store.state,
    }
  },
})
</script>
