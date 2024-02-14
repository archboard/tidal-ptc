<template>
  <Authenticated>
    <template #actions>
      <DropDownButton>
        {{ __('Actions') }}
        <template #dropdown>
          <SectionActionMenu :section="section" ignore-view />
        </template>
      </DropDownButton>
    </template>
    <template #after-title>
      <div class="flex items-center gap-2">
        <span>{{ section.teacher_display }}</span>
        <BookingDisabledPill v-if="!section.can_book" />
      </div>
    </template>

    <Table>
      <Thead>
        <tr>
          <Th>{{ __('Number') }}</Th>
          <Th>{{ __('Name') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="student in section.students" :key="student.id">
          <Td>{{ student.student_number }}</Td>
          <Td>
            <div class="flex items-center gap-2">
              <AppLink :href="`/students/${student.id}`">{{ student.name }}</AppLink>
              <BookingDisabledPill v-if="!student.can_book" />
            </div>
          </Td>
          <ActionColumn>
            <ContextMenu>
              <StudentActionMenu :student="student" />
            </ContextMenu>
          </ActionColumn>
        </tr>
        <tr v-if="section.students.length === 0">
          <Td colspan="3" class="text-center">{{ __('No records') }}</Td>
        </tr>
      </Tbody>
    </Table>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import SectionActionMenu from '@/components/actions/SectionActionMenu.vue'
import DropDownButton from '@/components/forms/buttons/DropDownButton.vue'
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'
import { Table, Tbody, Th, Thead, Td, ActionColumn } from '@/components/tables/index.js'
import ContextMenu from '@/components/ContextMenu.vue'
import StudentActionMenu from '@/components/actions/StudentActionMenu.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  section: Object,
})
</script>
