<template>
  <Authenticated>
    <template #actions>
      <DropDownButton v-if="can('student.update')">
        {{ __('Actions') }}
        <template #dropdown>
          <StudentActionMenu :student="student" ignore-view />
        </template>
      </DropDownButton>
    </template>
    <template #after-title>
      <Pill v-if="!student.can_book" color="yellow">{{ __('Booking disabled') }}</Pill>
    </template>

    <CardWrapper>
      <CardPadding>
        <CardHeader>{{ __('Enrollment') }}</CardHeader>
      </CardPadding>
      <Table no-top-radius>
        <Thead>
          <tr>
            <Th>{{ __('Course') }}</Th>
            <Th>{{ __('Section') }}</Th>
            <Th>{{ __('Teacher') }}</Th>
            <Th></Th>
          </tr>
        </Thead>
        <Tbody>
          <tr v-if="student.sections.length === 0">
            <Td colspan="4" class="text-center">
              {{ __('No records') }}
            </Td>
          </tr>
          <tr v-for="section in student.sections" :key="section.id">
            <Td>
              <div class="flex items-center gap-2">
                <AppLink :href="`/courses/${section.course.id}`">{{ section.course.name }}</AppLink>
                <BookingDisabledPill v-if="!section.course.can_book" />
              </div>
            </Td>
            <Td>
              <div class="flex items-center gap-2">
                <AppLink :href="`/sections/${section.id}`">{{ section.section_number }}</AppLink>
                <BookingDisabledPill v-if="!section.can_book" />
              </div>
            </Td>
            <Td>
              <div class="flex items-center gap-2">
                <span>{{ section.teacher_display }}</span>
                <BookingDisabledPill v-if="!section.teacher_can_book" />
              </div>
            </Td>
            <ActionColumn>
              <ContextMenu>
                <SectionActionMenu :section="section" />
              </ContextMenu>
            </ActionColumn>
          </tr>
        </Tbody>
      </Table>
    </CardWrapper>
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import DropDownButton from '@/components/forms/buttons/DropDownButton.vue'
import StudentActionMenu from '@/components/actions/StudentActionMenu.vue'
import Pill from '@/components/Pill.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import { Table, Thead, Th, Tbody, Td, ActionColumn } from '@/components/tables/index.js'
import ContextMenu from '@/components/ContextMenu.vue'
import SectionActionMenu from '@/components/actions/SectionActionMenu.vue'
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  student: Object,
})
</script>
