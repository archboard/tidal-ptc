<template>
  <Authenticated>
    <Filters
      v-model="filters"
      v-model:search="search"
      :available-filters="availableFilters"
      @update="updateResults()"
      :loading="updatingResults"
      :search-placeholder="__('Search by course…')"
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
          <Th>{{ __('Section') }}</Th>
          <Th>{{ __('Teacher') }}</Th>
          <Th>{{ __('Enrollment') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="section in sections.data" :key="section.id">
          <Td class="pr-0 w-4">
            <Checkbox v-model="selection" :value="section.id" @change="toggleSelection(section.id)" />
          </Td>
          <Td>
            <div class="flex items-center space-x-2">
              <BookingDisabledPill v-if="!section.course.can_book" />
              <AppLink :href="`/courses/${section.course.id}`">{{ section.course.name }} ({{ section.course.course_number }})</AppLink>
            </div>
          </Td>
          <Td>
            <div class="flex items-center space-x-2">
              <BookingDisabledPill v-if="!section.can_book" />
              <AppLink :href="`/sections/${section.id}`">{{ section.section_number }}</AppLink>
            </div>
          </Td>
          <Td>
            <div class="flex items-center space-x-2">
              <BookingDisabledPill v-if="!section.teacher_can_book" />
              <span class="whitespace-nowrap">{{ section.teacher_display }}</span>
            </div>
          </Td>
          <Td>{{ section.students_count }}</Td>
          <ActionColumn>
            <ContextMenu>
              <SectionActionMenu :section="section" />
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
import SectionActionMenu from '@/components/actions/SectionActionMenu.vue'

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
