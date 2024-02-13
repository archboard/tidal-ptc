<template>
  <Authenticated>
    <Filters
      v-model="filters"
      v-model:search="search"
      :available-filters="availableFilters"
      @update="updateResults()"
      :loading="updatingResults"
      :search-placeholder="__('Search by course name…')"
    />

    <SelectionManager :selection="selection" :select-none="selectNone">
      <div v-if="can('section.update')" class="p-1">
        <AppMenuItem
          is="InertiaLink"
          as="button"
          method="post"
          :href="`/selection/${model_alias}/hidden`"
          :data="{ can_book: false }"
        >
          {{ __('Disable booking') }}
        </AppMenuItem>
        <AppMenuItem
          is="InertiaLink"
          as="button"
          method="post"
          :href="`/selection/${model_alias}/hidden`"
          :data="{ can_book: true }"
        >
          {{ __('Enable booking') }}
        </AppMenuItem>
      </div>
    </SelectionManager>

    <Table no-top-radius>
      <Thead>
        <tr>
          <Th class="pr-0 w-4"><Checkbox @change="selectedAll = !selectedAll" :checked="selectedAll" /></Th>
          <Th>{{ __('Course') }}</Th>
          <Th>{{ __('Course number') }}</Th>
          <Th>{{ __('Section') }}</Th>
          <Th>{{ __('Enrollment') }}</Th>
          <Th v-if="can('section.update')"></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="section in sections.data" :key="section.id">
          <Td class="pr-0 w-4">
            <Checkbox v-model="selection" :value="section.id" @change="toggleSelection(section.id)" />
          </Td>
          <Td>
            <div class="flex items-center space-x-2">
              <AppLink :href="`/courses/${section.course.id}`">{{ section.course.name }}</AppLink>
              <BookingDisabledPill v-if="!section.course.can_book" />
            </div>
          </Td>
          <Td>{{ section.course.course_number }}</Td>
          <Td>
            <div class="flex items-center space-x-2">
              <span>{{ section.section_number }}</span>
              <BookingDisabledPill v-if="!section.can_book" />
            </div>
          </Td>
          <Td>{{ section.students_count }}</Td>
          <ActionColumn v-if="can('section.update')">
            <ContextMenu>

            </ContextMenu>
          </ActionColumn>
        </tr>
      </Tbody>
    </Table>

    <Pagination :links="sections.links" :meta="sections.meta" />
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import { ActionColumn, Table, Tbody, Th, Thead } from '@/components/tables/index.js'
import Td from '@/components/tables/Td.vue'
import Pagination from '@/components/tables/Pagination.vue'
import AppLink from '@/components/AppLink.vue'
import useModelSelection from '@/composition/useModelSelection.js'
import SelectionManager from '@/components/tables/SelectionManager.vue'
import Checkbox from '@/components/forms/Checkbox.vue'
import useFilters from '@/composition/useFilters.js'
import Filters from '@/components/tables/Filters.vue'
import AppMenuItem from '@/components/AppMenuItem.vue'
import ContextMenu from '@/components/ContextMenu.vue'
import Pill from '@/components/Pill.vue'
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'

const props = defineProps({
  sections: Object,
  availableFilters: {
    type: Array,
    default: () => [],
  },
  model_alias: String,
})
const { selection, selectedAll, selectNone, toggleSelection } = useModelSelection(props.model_alias)
const { filters, search, updateResults, updatingResults } = useFilters()
</script>
