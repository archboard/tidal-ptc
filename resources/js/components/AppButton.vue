<template>
  <component
    :is="component"
    class="border border-transparent overflow-hidden font-medium shadow hover:shadow-none focus:outline-none transition ease-in-out duration-150 relative text-center justify-center items-center disabled:cursor-not-allowed focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-700"
    :class="[
      buttonSize,
      buttonColor,
      {
        'flex w-full': full,
        'inline-flex': !full,
      }
    ]"
    :style="style"
    :disabled="loading"
  >
    <transition
      enter-active-class="transform transition duration-100 ease-in-out"
      enter-from-class="-translate-x-6 opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transform transition duration-100 ease-in-out"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="-translate-x-6 opacity-0"
    >
      <div
        v-show="loading"
        class="inline-flex items-center"
      >
        <Spinner :class="loaderSize" />
      </div>
    </transition>

    <transition
      enter-active-class="transform transition duration-100 ease-in-out"
      enter-from-class="translate-x-6 opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transform transition duration-100 ease-in-out"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="translate-x-6 opacity-0"
    >
      <span
        v-show="!loading"
        class="inline-flex items-center whitespace-nowrap space-x-1"
      >
        <slot>
          {{ __('Save') }}
        </slot>
      </span>
    </transition>
  </component>
</template>

<script>
import Spinner from '@/components/Spinner.vue'

export default {
  components: {
    Spinner,
  },

  props: {
    component: {
      type: String,
      default: 'button',
    },
    size: {
      type: String,
      default: 'base'
    },
    color: {
      type: String,
      default: 'primary'
    },
    loading: {
      type: Boolean,
      default: false
    },
    full: {
      type: Boolean,
      default: false
    }
  },

  data () {
    return {
      sizes: {
        xs: `px-2.5 py-1.5 text-xs leading-4 rounded-md`,
        sm: `px-3 py-1.5 text-sm leading-4 rounded-md`,
        base: `px-4 py-2 text-sm leading-5 rounded-lg`,
        lg: `px-4 py-2 sm:py-3 leading-6 rounded-xl`,
        xl: `px-4 sm:px-6 py-3 sm:text-lg leading-6 rounded-2xl`,
      },
      loaderSizes: {
        lg: 'h-6 w-6',
        xl: 'h-6 w-6',
      },
      style: {
        width: '',
        height: '',
      },
      colors: {
        primary: 'text-white bg-primary-600 focus:ring-primary-500 active:bg-primary-600 disabled:bg-gray-500',
        red: 'text-white bg-red-600 hover:bg-red-500 focus:border-red-700 focus:ring-red-400 active:bg-red-700 disabled:bg-red-400',
        yellow: 'text-white bg-yellow-600 hover:bg-yellow-500 focus:border-yellow-700 focus:ring-yellow-400 active:bg-yellow-700 disabled:bg-yellow-400',
        green: 'text-white bg-green-600 hover:bg-green-500 focus:border-green-700 focus:ring-green-400 active:bg-green-700 disabled:bg-green-400',
        gray: 'text-white bg-gray-500 hover:bg-gray-600 focus:border-gray-700 focus:ring-gray-400 active:bg-gray-700 disabled:bg-gray-400',
        gray_light: 'text-white bg-gray-300 hover:bg-gray-500 focus:border-gray-500 focus:ring-gray-200 active:bg-gray-500 disabled:bg-gray-200',
        white: 'text-gray-700 bg-white hover:bg-gray-50 border-gray-300 focus:border-gray-400 focus:ring-gray-400 active:bg-gray-100 disabled:bg-gray-400',
        purple: 'text-white bg-brand-purple focus:ring-brand-purple active:bg-brand-purple disabled:bg-gray-500'
      }
    }
  },

  computed: {
    buttonSize () {
      return this.sizes[this.size] || this.sizes.base
    },

    buttonColor () {
      return this.colors[this.color] || this.colors.white
    },

    loaderSize () {
      return this.loaderSizes[this.size] || 'h-5 w-5'
    },
  },

  watch: {
    loading () {
      if (!this.isBlock) {
        this.style.width = `${this.$el.offsetWidth}px`
      }

      this.style.height = `${this.$el.offsetHeight}px`
    }
  }
}
</script>
