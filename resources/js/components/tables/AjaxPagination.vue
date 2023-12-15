<template>
  <div class="bg-transparent flex items-center justify-between">
    <div v-if="meta.total > 0" class="flex-1 flex justify-between sm:hidden">
      <button @click.prevent="goto(prevPage)" :class="`rounded-md ${inactiveClass} ${buttonClass}`">
        {{ __('Previous') }}
      </button>
      <button @click.prevent="goto(nextPage)" :class="`rounded-md ml-3 ${inactiveClass} ${buttonClass}`">
        {{ __('Next') }}
      </button>
    </div>

    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
      <div>
        <p v-if="meta.total > 0" class="text-sm leading-5 text-gray-700 dark:text-gray-300 mb-0">
          {{ __('Showing :from to :to of :total results', { ...meta }) }}
        </p>
      </div>
      <div>
        <nav v-if="pageLinks.length > 1" class="relative z-0 inline-flex shadow-sm">
          <button @click.prevent="goto(prevPage)" :class="`rounded-l-md ${inactiveClass} ${buttonClass}`" aria-label="Previous">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
          </button>

          <component
            v-for="link in pageLinks"
            :key="link.page"
            :is="link.page ? 'button' : 'span'"
            @click.prevent="goto(link.page ? link.page : false)"
            :class="`-ml-px px-4 ${link.classes}`"
          >
            {{ link.page || '...' }}
          </component>

          <button @click.prevent="goto(nextPage)" :class="`-ml-px rounded-r-md ${inactiveClass} ${buttonClass}`" aria-label="Next">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import range from 'lodash/range'

export default defineComponent({
  props: {
    meta: {
      type: Object,
      default: () => ({})
    },
    pageLimit: {
      type: Number,
      default: 7,
    },
  },
  emits: ['paged'],

  data () {
    return {
      buttonClass: 'relative inline-flex items-center px-2 py-2 border border-gray-300 dark:border-gray-500 text-sm leading-5 font-medium text-gray-500 dark:text-gray-300 focus:z-10 focus:outline-none focus:ring-primary-500 focus:ring transition ease-in-out duration-150',
      inactiveClass: 'bg-white dark:bg-gray-800 dark:hover:bg-gray-500 active:bg-gray-100 active:text-gray-500 hover:text-gray-400',
      activeClass: 'bg-gray-200 hover:bg-gray-200 dark:bg-gray-700',
    }
  },

  computed: {
    pageLinks () {
      const pageRange = range(1, this.meta.last_page + 1).map(page => {
        return {
          page,
          classes: `${this.buttonClass} ${page === this.meta.current_page ? this.activeClass : this.inactiveClass}`
        }
      })

      return pageRange.length > this.pageLimit
        ? this.trimPageRange(pageRange)
        : pageRange
    },

    prevPage () {
      if (this.meta.current_page > 1) {
        return this.meta.current_page - 1
      }

      return this.meta.current_page
    },

    nextPage () {
      if (this.meta.current_page < this.meta.last_page) {
        return this.meta.current_page + 1
      }

      return this.meta.current_page
    }
  },

  methods: {
    goto (page) {
      this.$emit('paged', page)
    },

    trimPageRange (pageRange) {
      // If it's the first or last page, just get the beginning and end
      if (this.meta.current_page < 3 || this.meta.current_page > pageRange.length - 2) {
        const beginning = pageRange.slice(0, 3)
        beginning.push({ url: '#' })
        const end = pageRange.slice(pageRange.length - 3)

        return beginning.concat(end)
      }

      const first = pageRange.slice(0, 1)
      first.push({})

      const middle = pageRange.slice(this.meta.current_page - 2, this.meta.current_page + 1)
      middle.push({})

      const last = pageRange.slice(pageRange.length - 1)

      return first.concat(middle, last)
    }
  }
})
</script>
