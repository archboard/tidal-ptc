<template>
  <ul role="list" class="w-full border border-gray-200 rounded-md divide-y divide-gray-200">
    <li
      v-for="file in files"
      :key="file.id"
      class="pl-3 pr-4 py-3 flex items-center justify-between text-sm"
    >
      <div class="w-0 flex-1 flex items-center">
        <PaperClipIcon class="flex-shrink-0 h-5 w-5 text-gray-400" aria-hidden="true" />
        <span class="ml-2 flex-1 w-0 truncate">{{ file.name }}</span>
      </div>
      <div class="ml-4 flex-shrink-0">
        <AppLink is="a" :href="`/files/${file.id}/download`">{{ __('Download') }}</AppLink>
      </div>
    </li>
  </ul>
</template>

<script>
import { defineComponent } from 'vue'
import { PaperClipIcon } from '@heroicons/vue/24/outline'
import AppLink from '@/components/AppLink.vue'

export default defineComponent({
  components: {
    AppLink,
    PaperClipIcon,
  },
  props: {
    files: {
      type: Array,
      required: true,
    },
  },
})
</script>
