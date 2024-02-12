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

    <SelectionManager :selection="selection" :select-none="selectNone" />

    <Table no-top-radius>
      <Thead>
        <tr>
          <Th class="pr-0 w-4"><Checkbox @change="selectedAll = !selectedAll" :checked="selectedAll" /></Th>
          <Th>{{ __('Course') }}</Th>
          <Th>{{ __('Course number') }}</Th>
          <Th>{{ __('Section') }}</Th>
          <Th>{{ __('Enrollment') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="section in sections.data" :key="section.id">
          <Td class="pr-0 w-4">
            <Checkbox v-model="selection" :value="section.id" @change="toggleSelection(section.id)" />
          </Td>
          <Td>{{ section.course.name }}</Td>
          <Td>{{ section.course.course_number }}</Td>
          <Td>{{ section.section_number }}</Td>
          <Td>{{ section.students_count }}</Td>
          <ActionColumn>
            <AppLink :href="`/sections/${section.id}`">
              {{ __('View') }}
            </AppLink>
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
