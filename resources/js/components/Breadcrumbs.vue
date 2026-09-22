<template>
  <nav :class="['flex bg-white dark:bg-gray-900', { 'md:hidden': pages.length === 0 }]" aria-label="Breadcrumb">
    <Container class="w-full">
      <ol role="list" class="flex w-full space-x-4 py-4">
        <li class="flex md:hidden">
          <button type="button" class="-ms-2 pe-2 text-gray-500 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-gray-500" @click="emit('open-sidebar')">
            <span class="sr-only">{{ __('Open sidebar') }}</span>
            <Bars3BottomLeftIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </li>
        <li class="flex">
          <div class="flex items-center">
            <InertiaLink href="/" class="text-gray-400 dark:text-gray-200 hover:text-gray-500 dark:hover:text-gray-100">
              <HomeIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
              <span class="sr-only">Home</span>
            </InertiaLink>
          </div>
        </li>
        <li v-for="page in pages" :key="page.url" class="flex">
          <div class="flex items-center">
            <svg class="h-5 w-5 shrink-0 text-gray-200 dark:text-gray-700" viewBox="0 0 20 20" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
              <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
            </svg>
            <component
              :is="page.component"
              :as="page.as"
              :method="page.method"
              :target="page.target"
              :href="page.url || '#'"
              :class="['ms-4 text-sm font-medium', page.current ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-200 hover:text-gray-700 dark:hover:text-gray-100']"
              :aria-current="page.current ? 'page' : undefined"
            >
              {{ page.label }}
            </component>
          </div>
        </li>
      </ol>
    </Container>
  </nav>
</template>

<script setup>
import { HomeIcon } from '@heroicons/vue/20/solid'
import { Bars3BottomLeftIcon } from '@heroicons/vue/24/outline'
import useProp from '@/composition/useProp.js'
import Container from '@/components/Container.vue'

const pages = useProp('breadcrumbs')
const emit = defineEmits(['open-sidebar'])
</script>
