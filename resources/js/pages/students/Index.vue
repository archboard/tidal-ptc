<template>
  <Authenticated>
    <Filters
      v-model="filters"
      v-model:search="search"
      :available-filters="availableFilters"
      @update="updateResults()"
      :loading="updatingResults"
      :search-placeholder="__('Search by name or email…')"
    />

    <div v-if="selection.length > 0" class="px-4 sm:px-5 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600 text-sm">
      <div class="flex items-center space-x-2">
        <span>{{ __(':count selected', { count: selection.length }) }}</span>
        <AppLink is="button" @click.prevent="selectedAll = false">{{ __('Remove selection') }}</AppLink>
      </div>
    </div>

    <Table no-top-radius>
      <Thead>
        <tr>
          <Th class="pr-0 w-4"><Checkbox @change="selectedAll = !selectedAll" :checked="selectedAll" /></Th>
          <Th>{{ __('Number') }}</Th>
          <Th>{{ __('Grade') }}</Th>
          <Th>{{ __('Name') }}</Th>
          <Th>{{ __('Email') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="student in students.data" :key="student.id">
          <Td class="pr-0 w-4">
            <Checkbox v-model="selection" :value="student.id" @change="toggleSelection(student.id)" />
          </Td>
          <Td>{{ student.student_number }}</Td>
          <Td>{{ student.grade_level }}</Td>
          <Td>{{ student.name }}</Td>
          <Td>{{ student.email }}</Td>
          <ActionColumn>
            <AppLink :href="`/students/${student.id}`">
              {{ __('View') }}
            </AppLink>
          </ActionColumn>
        </tr>
      </Tbody>
    </Table>

    <Pagination :links="students.links" :meta="students.meta" />
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import { ActionColumn, Table, Tbody, Th, Thead } from '@/components/tables/index.js'
import Td from '@/components/tables/Td.vue'
import Pagination from '@/components/tables/Pagination.vue'
import ContextMenu from '@/components/ContextMenu.vue'
import AppMenuItem from '@/components/AppMenuItem.vue'
import Checkbox from '@/components/forms/Checkbox.vue'
import useModelSelection from '@/composition/useModelSelection.js'
import Filters from '@/components/tables/Filters.vue'
import useFilters from '@/composition/useFilters.js'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  students: Object,
  availableFilters: Array,
  currentFilters: [Array, Object],
})
const { selection, selectedAll, toggleSelection } = useModelSelection('student')
const { filters, search, updateResults, updatingResults } = useFilters()
</script>
