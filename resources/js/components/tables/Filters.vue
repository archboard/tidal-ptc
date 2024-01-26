<template>
  <div class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600 rounded-t-xl">
    <FadeIn>
      <div v-if="showFilters" class="px-4 sm:px-5 py-3">
        <div class="flex items-center justify-between">
          <h3 class="font-bold">{{ __('Filters') }}</h3>
          <div>
            <button class="text-red-500 font-medium" @click.prevent="reset">{{ __('Reset') }}</button>
          </div>
        </div>
        <div>
          <div v-for="(filter, id) in localValue" :key="id" class="mt-4 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
            <div class="flex items-center justify-between px-4 sm:px-5 py-3">
              <div class="flex items-center text-sm">
                <button @click.prevent="toggleExpansion(id)" class="font-medium">{{ filtersByKey[filter.key].label }}</button>
                <FadeIn>
                  <div v-show="!expandedFilters[id]" class="ml-4 flex space-x-2">
                    <span>{{ filtersByKey[filter.key].operators[filter.operator] }}</span>
                    <span>{{ displayValue(filter) }}</span>
                  </div>
                </FadeIn>
              </div>
              <div class="space-x-3 flex items-center">
                <button @click.prevent="removeFilter(id)">
                  <span class="sr-only">Delete filter</span>
                  <TrashIcon class="w-5 h-5 text-red-500 dark:text-red-400" />
                </button>

                <button @click.prevent="toggleExpansion(id)">
                  <span class="sr-only">Show or hide filter</span>
                  <ChevronUpIcon v-if="expandedFilters[id]" class="w-5 h-5 text-gray-500 dark:text-gray-300" />
                  <ChevronDownIcon v-else class="w-5 h-5 text-gray-500 dark:text-gray-300" />
                </button>
              </div>
            </div>
            <FadeIn>
              <div v-show="expandedFilters[id]" class="px-4 sm:px-5 py-3 sm:grid sm:grid-cols-2 space-y-2 sm:space-y-0 sm:gap-x-4 border-t border-gray-200 dark:border-gray-600">
                <div>
                  <AppSelect v-model="filter.operator" :options="filtersByKey[filter.key].operators" />
                </div>
                <div>
                  <component
                    :is="components[filtersByKey[filter.key].component]"
                    v-model="filter.value"
                    v-bind="filtersByKey[filter.key].props"
                  />
                </div>
              </div>
            </FadeIn>
          </div>
        </div>

        <div class="text-center pt-3 relative z-0">
          <DropDownButton :icon="PlusIcon" color="white">
            {{ __('Add filter')}}

            <template #dropdown>
              <div class="p-1">
                <AppMenuItem
                  v-for="filter in availableFilters"
                  :key="filter.key"
                  @click="addFilter(filter)"
                >
                  {{ filter.label }}
                </AppMenuItem>
              </div>
            </template>
          </DropDownButton>
        </div>

        <div class="flex items-center space-x-2 mt-2">
          <AppButton @click.prevent="$emit('update')" :loading="loading">{{ __('Apply filters') }}</AppButton>
          <AppButton @click.prevent="showFilters = false" color="white">{{ __('Close') }}</AppButton>
        </div>
      </div>
    </FadeIn>
    <div class="px-4 py-3 sm:flex sm:items-center sm:justify-between">
      <div class="relative w-full sm:w-[350px]">
        <label for="search-filter" class="sr-only">Search results</label>
        <SearchInput v-model="localSearch" id="search-filter" :placeholder="searchPlaceholder" />
      </div>
      <div class="flex w-full justify-end space-x-2 mt-4 sm:mt-0">
        <button v-if="Object.keys(localValue).length > 0" @click.prevent="reset">
          <span class="sr-only">Reset filters</span>
          <TrashIcon class="h-5 w-5 text-red-500 dark:text-red-400" />
        </button>
        <button v-if="availableFilters.length > 0" class="relative block" @click.prevent="showFilters = !showFilters">
          <span class="absolute -top-2 -right-2 inline-flex items-center rounded-md bg-primary-400/20 px-1 text-xs font-medium text-primary-400 ring-1 ring-inset ring-primary-400/20">{{ Object.keys(localValue).length }}</span>
          <FunnelIcon class="w-5 h-5 text-gray-500 dark:text-gray-300" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useVModel } from '@vueuse/core'
import { FunnelIcon, PlusIcon, ChevronUpIcon, ChevronDownIcon, TrashIcon } from '@heroicons/vue/24/solid'
import FadeIn from '@/components/transitions/FadeIn.vue'
import DropDownButton from '@/components/forms/buttons/DropDownButton.vue'
import AppMenuItem from '@/components/AppMenuItem.vue'
import { nanoid } from 'nanoid'
import AppSelect from '@/components/forms/AppSelect.vue'
import AppInput from '@/components/forms/AppInput.vue'
import CheckboxGroup from '@/components/filters/CheckboxGroup.vue'
import AppButton from '@/components/AppButton.vue'
import MultiSelectCombobox from '@/components/filters/MultiSelectCombobox.vue'
import SearchInput from '@/components/forms/SearchInput.vue'

const props = defineProps({
  modelValue: [Array, Object],
  search: String,
  availableFilters: {
    type: Array,
    required: true,
  },
  loading: Boolean,
  searchPlaceholder: String,
})
const emit = defineEmits(['update', 'update:modelValue', 'update:search'])
const localValue = useVModel(props, 'modelValue', emit)
const localSearch = useVModel(props, 'search', emit)
const components = {
  text: AppInput,
  select: AppSelect,
  checkbox_group: CheckboxGroup,
  combobox: MultiSelectCombobox,
}
const showFilters = ref(false)
const expandedFilters = ref({})
const filtersByKey = computed(() => {
  return props.availableFilters.reduce((carry, filter) => {
    carry[filter.key] = filter
    return carry
  }, {})
})
const addFilter = config => {
  const id = nanoid(3)

  localValue.value[id] = {
    key: config.key,
    operator: Object.keys(config.operators)[0],
    value: config.defaultValue || null,
  }
  expandedFilters.value[id] = true
}
const removeFilter = id => {
  delete localValue.value[id]
  delete expandedFilters.value[id]
}
const reset = () => {
  localValue.value = {}
  localSearch.value = null
  expandedFilters.value = {}
  emit('update')
}
const toggleExpansion = id => {
  expandedFilters.value[id] = !expandedFilters.value[id]
}
const displayValue = filter => {
  if (Array.isArray(filter.value)) {
    return Object.keys(filtersByKey.value[filter.key].props.options)
      .reduce((carry, option) => {
        if (filter.value.includes(option)) {
          carry.push(filtersByKey.value[filter.key].props.options[option])
        }

        return carry
      }, [])
      .join(', ')
  }

  return filter.value
}
</script>
