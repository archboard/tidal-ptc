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

    <SelectionManager :selection="selection" :select-none="selectNone">
      <div v-if="can('student.update')" class="p-1">
        <AppMenuItem
          is="InertiaLink"
          as="button"
          method="post"
          href="/selection/student/hidden"
          :data="{ can_book: false }"
        >
          {{ __('Disable booking') }}
        </AppMenuItem>
        <AppMenuItem
          is="InertiaLink"
          as="button"
          method="post"
          href="/selection/student/hidden"
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
          <Th>{{ __('Number') }}</Th>
          <Th>{{ __('Grade') }}</Th>
          <Th>{{ __('Name') }}</Th>
          <Th v-if="can('student.update')"></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="student in students.data" :key="student.id">
          <Td class="pr-0 w-4">
            <Checkbox v-model="selection" :value="student.id" @change="toggleSelection(student.id)" />
          </Td>
          <Td>{{ student.student_number }}</Td>
          <Td>{{ student.grade_level }}</Td>
          <Td>
            <div class="flex items-center space-x-2">
              <AppLink :href="`/students/${student.id}`">{{ student.name }}</AppLink>
              <BookingDisabledPill v-if="!student.can_book" />
            </div>
          </Td>
          <ActionColumn v-if="can('student.update')">
            <ContextMenu>
              <StudentActionMenu :student="student" />
            </ContextMenu>
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
import Pill from '@/components/Pill.vue'
import StudentActionMenu from '@/components/actions/StudentActionMenu.vue'
import SelectionManager from '@/components/tables/SelectionManager.vue'
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  students: Object,
  availableFilters: Array,
  currentFilters: [Array, Object],
})
const { selection, selectedAll, selectNone, toggleSelection } = useModelSelection('student')
const { filters, search, updateResults, updatingResults } = useFilters()
</script>
