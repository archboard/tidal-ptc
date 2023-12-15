<template>
  <div v-if="meta.total > 0" class="bg-transparent py-6 flex items-center justify-between">
    <div v-if="meta.total > 0" class="flex-1 flex justify-between sm:hidden">
      <component :is="prevPage ? 'InertiaLink' : 'button'" :href="prevPage" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 text-sm leading-5 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
        Previous
      </component>
      <component :is="nextPage ? 'InertiaLink' : 'button'" :href="nextPage" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 text-sm leading-5 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:text-gray-500 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
        Next
      </component>
    </div>

    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
      <div>
        <p class="text-sm leading-5 text-gray-700 dark:text-gray-300 mb-0">
          {{ __('Showing :from to :to of :total results', { ...meta }) }}
        </p>
      </div>
      <div>
        <nav v-if="pageLinks.length > 1" class="relative z-0 inline-flex shadow-sm">
          <component :is="prevPage ? 'InertiaLink' : 'button'" :href="prevPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-800 text-sm leading-5 font-medium text-gray-500 dark:text-gray-300 hover:text-gray-400 dark:hover:bg-gray-500 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150" aria-label="Previous">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
          </component>

          <component
            v-for="link in pageLinks"
            :key="link.url"
            :is="link.label ? 'InertiaLink' : 'span'"
            :href="link.url"
            class="-ml-px relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-500 text-sm leading-5 font-medium text-gray-700 dark:text-gray-200 transition ease-in-out duration-200"
            :class="{
              'bg-gray-200 hover:bg-gray-200 dark:bg-gray-700': link.active,
              'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-600 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-700': !link.active
            }"
          >
            {{ link.label || '...' }}
          </component>

          <component :is="nextPage ? 'InertiaLink' : 'button'" :href="nextPage" class="-ml-px relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-800 text-sm leading-5 font-medium text-gray-500 dark:text-gray-300 hover:text-gray-400 dark:hover:bg-gray-500 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150" aria-label="Next">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
          </component>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import range from 'lodash/range'
import qs from 'qs'

export default defineComponent({
  props: {
    links: Object,
    meta: Object,
    pageLimit: {
      type: Number,
      default: 7,
    },
  },

  computed: {
    pageLinks () {
      const pageLimit = this.pageLimit
      const parsed = qs.parse(window.location.search.substr(1))

      const pageRange = range(1, this.meta.last_page + 1).map(page => {
        parsed.page = page

        return {
          label: page,
          url: `${this.meta.path}?${qs.stringify(parsed, { encodeValuesOnly: true })}`,
          active: page === this.meta.current_page
        }
      })

      return pageRange.length > pageLimit
        ? this.trimPageRange(pageRange)
        : pageRange
    },

    prevPage () {
      const parsed = qs.parse(window.location.search.substr(1))
      parsed.page = parsed.page ? parseFloat(parsed.page) : 1

      if (parsed.page > 1) {
        parsed.page = parsed.page - 1

        return `${this.meta.path}?${qs.stringify(parsed, { encodeValuesOnly: true })}`
      }

      return null
    },

    nextPage () {
      const parsed = qs.parse(window.location.search.substr(1))
      parsed.page = parsed.page ? parseFloat(parsed.page) : 1

      if (parsed.page < this.meta.last_page) {
        parsed.page = parsed.page + 1

        return `${this.meta.path}?${qs.stringify(parsed, { encodeValuesOnly: true })}`
      }

      return null
    }
  },

  methods: {
    trimPageRange (pageRange) {
      // If it's the first or last page, just get the beginning and end
      if (this.meta.current_page < 3 || this.meta.current_page > pageRange.length - 2) {
        const beginning = pageRange.slice(0, 3)
        beginning.push({ url: '#' })
        const end = pageRange.slice(pageRange.length - 3)

        return beginning.concat(end)
      }

      const first = pageRange.slice(0, 1)
      first.push({ url: '#' })

      const middle = pageRange.slice(this.meta.current_page - 2, this.meta.current_page + 1)
      middle.push({ url: '#1' })

      const last = pageRange.slice(pageRange.length - 1)

      return first.concat(middle, last)
    }
  }
})
</script>
