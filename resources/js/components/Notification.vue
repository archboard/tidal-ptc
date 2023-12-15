<template>
  <div class="w-full bg-white dark:bg-gray-600 mt-4 shadow-lg rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
    <div class="p-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <!-- Notification icon -->
          <component :is="icon" class="h-6 w-6" :class="color"></component>
        </div>
        <div class="ml-3 w-0 flex-1 pt-0.5">
          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ title }}
          </p>
          <p v-html="notification.text" class="mt-1 text-sm text-gray-500 dark:text-gray-300"></p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <!-- Close icon -->
          <button @click.prevent="removeNotification(notification.id)" class="p-0.5 bg-white dark:bg-gray-600 rounded-full inline-flex text-gray-400 dark:text-gray-300 hover:text-gray-500 hover:bg-gray-50 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
            <span class="sr-only">Close</span>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import store from '@/stores/notifications'
import { CheckCircleIcon, InformationCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline/index.js'

export default defineComponent({
  props: {
    notification: {
      type: Object,
      required: true
    }
  },

  setup ({ notification }) {
    const colors = {
      success: `text-green-400`,
      error: `text-red-400`,
      warning: `text-yellow-400`,
      neutral: `text-gray-400`,
    }
    const titles = {
      success: `Success!`,
      error: `Error!`,
      warning: `Warning!`,
      neutral: 'Hey!',
    }
    const icons = {
      success: CheckCircleIcon,
      error: ExclamationCircleIcon,
      warning: ExclamationCircleIcon,
      neutral: InformationCircleIcon,
    }

    return {
      color: notification.color || colors[notification.level] || colors.neutral,
      title: notification.title || titles[notification.level] || titles.neutral,
      icon: icons[notification.level] || icons.neutral,
      state: store.state,
      removeNotification: store.removeNotification,
    }
  }
})
</script>
