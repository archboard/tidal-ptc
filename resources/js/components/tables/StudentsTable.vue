<template>
  <Table>
    <Thead>
      <tr>
        <Th>{{ __('Number') }}</Th>
        <Th>{{ __('Name') }}</Th>
        <Th>{{ __('Grade') }}</Th>
        <Th></Th>
      </tr>
    </Thead>
    <Tbody>
      <tr v-for="student in students" :key="student.id">
        <Td>{{ student.student_number }}</Td>
        <Td>
          <div class="flex items-center gap-2">
            <AppLink :href="`/students/${student.id}`">{{ student.name }}</AppLink>
            <BookingDisabledPill v-if="!student.can_book" />
          </div>
        </Td>
        <Td>{{ student.grade_level }}</Td>
        <ActionColumn>
          <ContextMenu>
            <StudentActionMenu :student="student" />
          </ContextMenu>
        </ActionColumn>
      </tr>
      <tr v-if="students.length === 0">
        <Td colspan="4" class="text-center">{{ __('No records') }}</Td>
      </tr>
    </Tbody>
  </Table>
</template>

<script setup>
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'
import { Table, Tbody, Th, Thead, Td, ActionColumn } from '@/components/tables/index.js'
import ContextMenu from '@/components/ContextMenu.vue'
import StudentActionMenu from '@/components/actions/StudentActionMenu.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  students: Array,
})
</script>
