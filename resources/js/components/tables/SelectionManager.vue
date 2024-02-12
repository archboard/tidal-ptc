<template>
  <div v-if="selection.length > 0" class="px-4 sm:px-5 py-3 flex justify-between items-center bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600 text-sm">
    <div class="flex items-center space-x-2">
      <span>{{ __(':count selected', { count: selection.length }) }}</span>
      <AppLink is="button" @click.prevent="selectNone()">{{ __('Remove selection') }}</AppLink>
    </div>

    <div>
      <ContextMenu>
        <template #icon>
          <span class="flex items-center gap-1">
            <AppLink is="span">{{ __('Actions') }}</AppLink>
            <ChevronDownIcon class="h-4 w-4" />
          </span>
        </template>

        <slot />
      </ContextMenu>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppLink from '@/components/AppLink.vue'
import ContextMenu from '@/components/ContextMenu.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
  selection: Array,
  selectNone: Function,
})
const emit = defineEmits([])
</script>
