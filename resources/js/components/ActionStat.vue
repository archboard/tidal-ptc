<template>
  <div class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900 px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
    <dt>
      <div class="absolute rounded-md bg-primary-500 p-3">
        <component :is="icon" class="h-6 w-6 text-white" />
      </div>
      <p class="ml-16 truncate text-sm font-medium text-gray-500 dark:text-gray-400">
        <slot />
      </p>
    </dt>
    <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
      <p class="text-2xl font-semibold text-gray-900 dark:text-gray-50">{{ value }}</p>
      <div class="absolute inset-x-0 bottom-0 bg-gray-50 dark:bg-gray-800 px-4 py-4 sm:px-6">
        <div class="text-sm">
          <button :disabled="loading" @click.prevent="$emit('action')" class="flex items-center space-x-2 font-medium text-primary-600 hover:text-primary-500 disabled:cursor-not-allowed">
            <span>{{ actionText }}</span>
            <Spinner v-if="loading" class="w-4 h-4 text-gray-500 dark:text-gray-300" />
          </button>
        </div>
      </div>
    </dd>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Spinner from '@/components/Spinner.vue'

const props = defineProps({
  icon: [Object, Function],
  value: [String, Number],
  actionText: String,
  loading: Boolean,
})
const emit = defineEmits(['action'])
</script>
