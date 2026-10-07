<template>
  <div class="relative isolate flex min-h-screen flex-col overflow-hidden bg-primary-50 dark:bg-primary-950">
    <header class="px-6 pt-6 sm:px-10">
      <a href="/" class="inline-flex items-center gap-2 font-semibold text-primary-900 dark:text-primary-100">
        <Logo class="h-9 w-auto" />
        <span>{{ appName }}</span>
      </a>
    </header>

    <main class="relative z-10 mx-auto flex w-full max-w-3xl flex-1 flex-col items-center justify-center px-6 pb-56 text-center">
      <Logo class="bob mb-6 h-16 w-auto drop-shadow-lg" aria-hidden="true" />

      <p class="bg-linear-to-b from-primary-400 to-primary-800 bg-clip-text text-[7rem] leading-none font-black tracking-tighter text-transparent sm:text-[11rem] dark:from-primary-200 dark:to-primary-500">
        {{ status }}
      </p>

      <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-primary-950 sm:text-5xl dark:text-white">
        {{ copy.title }}
      </h1>

      <p class="mt-4 max-w-xl text-lg text-primary-900/80 dark:text-primary-100/80">
        {{ message ?? copy.description }}
      </p>

      <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <AppButton
          v-for="link in links"
          :key="link.href"
          component="a"
          :href="link.href"
          size="lg"
        >
          {{ link.label }}
        </AppButton>

        <template v-if="!links.length">
          <AppButton component="a" href="/" size="lg">
            {{ __('Back to shore') }}
          </AppButton>
          <AppButton v-if="status >= 500" component="a" :href="currentUrl" size="lg" color="white">
            {{ __('Try again') }}
          </AppButton>
        </template>
      </div>
    </main>

    <!-- ponytail: pure CSS waves, two layers drifting at different speeds -->
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-48 sm:h-64" aria-hidden="true">
      <svg class="wave wave-back absolute bottom-0 h-full w-[200%] text-primary-300 dark:text-primary-800" viewBox="0 0 2880 320" preserveAspectRatio="none">
        <path fill="currentColor" d="M0 160c240 60 480 60 720 0s480-60 720 0 480 60 720 0 480-60 720 0v160H0z" />
      </svg>
      <svg class="wave wave-front absolute bottom-0 h-3/4 w-[200%] text-primary-500 dark:text-primary-600" viewBox="0 0 2880 320" preserveAspectRatio="none">
        <path fill="currentColor" d="M0 200c120-50 240-50 360 0s240 50 360 0 240-50 360 0 240 50 360 0 240-50 360 0 240 50 360 0 240-50 360 0 240 50 360 0v120H0z" />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { trans as __ } from 'laravel-vue-i18n'
import Logo from '@/components/icons/Logo.vue'
import AppButton from '@/components/AppButton.vue'

const props = defineProps({
  status: Number,
  message: String,
  links: {
    type: Array,
    default: () => [],
  },
})

const appName = import.meta.env.APP_NAME
const currentUrl = typeof window !== 'undefined' ? window.location.href : '/'

const copy = computed(() => ({
  503: { title: __('Low tide'), description: __('We are doing some maintenance. Please check back soon.') },
  500: { title: __('Choppy waters'), description: __('Something went wrong on our end. We have been notified and are on it.') },
  404: { title: __('Lost at sea'), description: __('The page you are looking for drifted away or never existed.') },
  403: { title: __('No swimming here'), description: __('You do not have permission to access this page.') },
  402: { title: __('At full capacity'), description: __('Your school license limit has been reached.') },
}[props.status] ?? { title: __('Something went wrong'), description: __('Please try again.') }))
</script>

<style scoped>
.wave {
  animation: drift linear infinite;
}

.wave-back {
  animation-duration: 18s;
  opacity: 0.6;
}

.wave-front {
  animation-duration: 11s;
  animation-direction: reverse;
}

.bob {
  animation: bob 4s ease-in-out infinite;
}

@keyframes drift {
  to { transform: translateX(-50%); }
}

@keyframes bob {
  0%, 100% { transform: translateY(0) rotate(-4deg); }
  50% { transform: translateY(-10px) rotate(4deg); }
}

@media (prefers-reduced-motion: reduce) {
  .wave, .bob {
    animation: none;
  }
}
</style>
