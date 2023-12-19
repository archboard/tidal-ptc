<template>
  <div>
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog as="div" class="relative z-40 md:hidden" @close="sidebarOpen = false">
        <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-gray-600 bg-opacity-75" />
        </TransitionChild>

        <div class="fixed inset-0 z-40 flex">
          <TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0" leave-to="-translate-x-full">
            <DialogPanel class="relative flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4">
              <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                  <button type="button" class="ml-1 flex h-10 w-10 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                    <span class="sr-only">Close sidebar</span>
                    <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                  </button>
                </div>
              </TransitionChild>
              <div class="flex flex-shrink-0 items-center px-4">
                <Logo class="h-8 w-auto" />
              </div>
              <div class="mt-5 h-0 flex-1 overflow-y-auto">
                <nav class="space-y-1 px-2">
                  <component
                    v-for="item in props.navigation"
                    :key="item.url"
                    :is="item.component"
                    :target="item.target"
                    :method="item.method"
                    :href="item.url"
                    :as="item.as"
                    :class="[item.current ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center px-2 py-2 text-base font-medium rounded-md']"
                  >
                    <div v-html="item.icon" :class="[item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-4 flex-shrink-0 h-6 w-6']" aria-hidden="true" />
                    {{ item.label }}
                  </component>
                </nav>
              </div>
            </DialogPanel>
          </TransitionChild>
          <div class="w-14 flex-shrink-0" aria-hidden="true">
            <!-- Dummy element to force sidebar to shrink to fit close icon -->
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Static sidebar for desktop -->
    <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
      <!-- Sidebar component, swap this element with another sidebar if you like -->
      <div class="flex flex-grow flex-col overflow-y-auto border-r border-primary-200 dark:border-transparent bg-primary-100 dark:bg-primary-900 pt-5 pb-4">
        <div class="flex flex-shrink-0 items-center px-4">
          <Logo class="h-10 w-auto" />
        </div>

        <div v-if="adminSchools.length > 1" class="mt-5 px-2">
          <label for="current-school" class="sr-only">Current school</label>
          <AppSelect v-model="currentSchool" class="bg-primary-200 dark:bg-primary-800 border-primary-300 dark:border-primary-900">
            <option v-for="school in adminSchools" :id="school.id" :value="school.id">{{ school.name }}</option>
          </AppSelect>
        </div>

        <div class="mt-5 flex flex-grow flex-col">
          <nav class="flex-1 space-y-8 px-2" aria-label="Sidebar">
            <div class="space-y-1">
              <component
                v-for="item in props.navigation"
                :key="item.url"
                :is="item.component"
                :target="item.target"
                :method="item.method"
                :href="item.url"
                :as="item.as"
                :class="[item.current ? 'bg-primary-50 dark:bg-primary-800 text-primary-900 dark:text-white' : 'text-primary-800 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-primary-700 hover:text-primary-900 dark:hover:text-gray-100', 'group flex items-center px-2 py-2 text-sm font-medium rounded-md']"
              >
                <div v-html="item.icon" :class="[item.current ? 'text-primary-500 dark:text-gray-300' : 'text-primary-400 dark:text-gray-300 group-hover:text-primary-500 dark:group-hover:text-gray-300', 'mr-3 flex-shrink-0']" aria-hidden="true" />
                {{ item.label }}
              </component>
            </div>
            <div class="space-y-1">
              <h3 class="px-3 text-sm font-medium text-primary-700 dark:text-gray-200">{{ __('Settings') }}</h3>
              <div class="space-y-1" role="group">
                <component
                  v-for="item in props.secondaryNav"
                  :key="item.url"
                  :is="item.component"
                  :target="item.target"
                  :method="item.method"
                  :href="item.url"
                  :as="item.as"
                  class="group flex items-center w-full rounded-md px-3 py-2 text-sm font-medium text-primary-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-primary-800 hover:text-gray-900 dark:hover:text-gray-100"
                >
                  <span class="truncate">{{ item.label }}</span>
                </component>
              </div>
            </div>
          </nav>
        </div>
      </div>
    </div>

    <div class="flex flex-1 min-h-screen flex-col justify-between md:pl-64">
      <div class="sticky top-0 z-10 flex h-16 flex-shrink-0 bg-white dark:bg-gray-800 shadow">
        <button type="button" class="border-r border-gray-200 dark:border-gray-600 px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-500 md:hidden" @click="sidebarOpen = true">
          <span class="sr-only">Open sidebar</span>
          <Bars3BottomLeftIcon class="h-6 w-6" aria-hidden="true" />
        </button>
        <div class="flex flex-1 justify-between px-4">
          <div class="flex flex-1">
            <form class="flex w-full md:ml-0" action="#" method="GET">
              <label for="search-field" class="sr-only">Search</label>
              <div class="relative w-full text-gray-400 focus-within:text-gray-600 dark:focus-within:text-gray-200">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
                  <MagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />
                </div>
                <input id="search-field" class="block h-full w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-transparent py-2 pl-8 pr-3 placeholder-gray-500 dark:placeholder-gray-300 focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:text-sm" placeholder="Search" type="search" name="search" />
              </div>
            </form>
          </div>
          <div class="ml-4 flex items-center md:ml-6">
            <button @click.prevent="toggleTheme" type="button" class="sr-hidden rounded-full bg-white dark:bg-gray-800 p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
              <MoonIcon v-if="isDark" class="h-6 w-6" aria-hidden="true" />
              <SunIcon v-else class="h-6 w-6" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <main class="flex-1">
        <div class="py-6">
          <Container>
            <div class="md:flex justify-between items-start">
              <h1 v-if="title" class="text-2xl font-semibold">{{ title }}</h1>

              <slot name="actions" />
            </div>

            <div class="py-4">
              <slot />
            </div>
          </Container>
        </div>
      </main>

      <Footer />
    </div>

    <TimezoneBanner />
  </div>

  <Notifications />
  <Modal />
</template>

<script setup>
import { inject, ref, watch } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Bars3BottomLeftIcon, SunIcon, MoonIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import Logo from '@/components/icons/Logo.vue'
import Footer from '@/components/Footer.vue'
import Notifications from '@/components/Notifications.vue'
import TimezoneBanner from '@/components/banners/TimezoneBanner.vue'
import usePageTitle from '@/composition/usePageTitle.js'
import { router, usePage } from '@inertiajs/vue3'
import { Modal } from 'momentum-modal'
import useProp from '@/composition/useProp.js'
import AppSelect from '@/components/forms/AppSelect.vue'
import Container from '@/components/Container.vue'
import useColorTheme from '@/composition/useColorTheme.js'

const title = usePageTitle()
const { props } = usePage()
const sidebarOpen = ref(false)
const { isDark, toggleTheme } = useColorTheme()
const adminSchools = useProp('adminSchools')
const user = useProp('user')
const $error = inject('$error')
const currentSchool = ref(user.value.school_id)

watch(currentSchool, (value) => {
  if (value) {
    router.put('/settings/current-school', {
      school_id: value
    }, {
      preserveScroll: true,
      onError: (errors) => {
        $error(errors.school_id)
      }
    })
  }
})
</script>
