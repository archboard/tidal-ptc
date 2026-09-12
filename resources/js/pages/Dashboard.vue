<template>
  <Authenticated>
    <!-- Guardian -->
    <div v-if="user.user_type === 'guardian'" class="space-y-6">
      <CardWrapper v-if="!bookingOpen">
        <CardPadding>
          <CardHeader>{{ __('Booking is closed') }}</CardHeader>
          <HelpText v-if="opensAt">{{ __('Booking opens :when.', { when: displayDate(opensAt, 'full', true) }) }}</HelpText>
        </CardPadding>
      </CardWrapper>

      <CardWrapper>
        <CardPadding>
          <CardHeader>{{ __('Your conferences') }}</CardHeader>
          <HelpText v-if="bookingOpen && closesAt">{{ __('Booking closes :when.', { when: displayDate(closesAt, 'full', true) }) }}</HelpText>
        </CardPadding>
        <Table no-top-radius>
          <Thead>
            <tr>
              <Th>{{ __('When') }}</Th>
              <Th>{{ __('Student') }}</Th>
              <Th>{{ __('Teacher') }}</Th>
              <Th>{{ __('Where') }}</Th>
              <Th></Th>
            </tr>
          </Thead>
          <Tbody>
            <tr v-if="reservations.length === 0">
              <Td colspan="5">{{ __('No upcoming conferences.') }}</Td>
            </tr>
            <tr v-for="slot in reservations" :key="slot.id">
              <Td>{{ displayDate(slot.starts_at, 'full') }}</Td>
              <Td>{{ slot.student?.name }}</Td>
              <Td>{{ slot.user?.name }}</Td>
              <Td>{{ slot.requested_online || slot.is_online ? __('Online') : (slot.location || '—') }}</Td>
              <ActionColumn>
                <div class="flex gap-3 justify-end">
                  <AppLink v-if="bookingOpen" :href="`/reservations/create/${slot.student_id}/${slot.user?.id}`">{{ __('Move') }}</AppLink>
                  <ConfirmButton color="red" class="text-sm" @confirmed="close => cancel(slot, close)">
                    {{ __('Cancel') }}
                    <template #actionText>{{ __('Cancel conference') }}</template>
                  </ConfirmButton>
                </div>
              </ActionColumn>
            </tr>
          </Tbody>
        </Table>
      </CardWrapper>

      <CardWrapper v-for="student in students" :key="student.id">
        <CardPadding>
          <CardHeader>{{ student.name }}</CardHeader>
          <HelpText v-if="!student.can_book">{{ __('Booking is not available for this student.') }}</HelpText>
        </CardPadding>
        <Table no-top-radius>
          <Thead>
            <tr>
              <Th>{{ __('Course') }}</Th>
              <Th>{{ __('Teacher') }}</Th>
              <Th></Th>
            </tr>
          </Thead>
          <Tbody>
            <tr v-for="row in teacherRows(student)" :key="row.key">
              <Td>{{ row.course }}</Td>
              <Td>{{ row.teacher.name }}</Td>
              <ActionColumn>
                <span v-if="reservationFor(student, row.teacher)">{{ __('Booked') }}</span>
                <AppLink v-else-if="bookingOpen && student.can_book && row.bookable" :href="`/reservations/create/${student.id}/${row.teacher.id}`">
                  {{ __('Book') }}
                </AppLink>
              </ActionColumn>
            </tr>
          </Tbody>
        </Table>
      </CardWrapper>
    </div>

    <!-- Staff -->
    <div v-else-if="user.user_type === 'staff'" class="space-y-6">
      <dl v-if="schoolStats" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div v-for="stat in stats" :key="stat.label" class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm px-5 py-4">
          <dt class="text-sm text-gray-500 dark:text-gray-300 truncate">{{ stat.label }}</dt>
          <dd class="text-2xl font-semibold">{{ stat.value }}</dd>
        </div>
      </dl>

      <CardWrapper>
        <CardPadding class="flex items-center justify-between gap-4">
          <div>
            <CardHeader>{{ __('My conferences') }}</CardHeader>
            <HelpText>{{ __(':count open time slots remaining.', { count: openCount }) }}</HelpText>
          </div>
          <AppLink v-if="canManageTimeSlots" href="/time-slots/create">{{ __('Manage time slots') }}</AppLink>
        </CardPadding>
        <Table no-top-radius>
          <Thead>
            <tr>
              <Th>{{ __('When') }}</Th>
              <Th>{{ __('Student') }}</Th>
              <Th>{{ __('Contact') }}</Th>
              <Th>{{ __('Notes') }}</Th>
              <Th></Th>
            </tr>
          </Thead>
          <Tbody>
            <tr v-if="myReservations.length === 0">
              <Td colspan="5">{{ __('No upcoming conferences.') }}</Td>
            </tr>
            <tr v-for="slot in myReservations" :key="slot.id">
              <Td>{{ displayDate(slot.starts_at, 'full') }}</Td>
              <Td>{{ slot.student?.name }}</Td>
              <Td>{{ slot.reserved_by?.name }}</Td>
              <Td class="max-w-xs truncate">{{ slot.contact_notes }}</Td>
              <ActionColumn>
                <ConfirmButton color="red" class="text-sm" @confirmed="close => cancel(slot, close)">
                  {{ __('Cancel') }}
                  <template #actionText>{{ __('Cancel conference') }}</template>
                </ConfirmButton>
              </ActionColumn>
            </tr>
          </Tbody>
        </Table>
      </CardWrapper>
    </div>

    <CardWrapper v-else>
      <CardPadding>{{ __("You're logged in.") }}</CardPadding>
    </CardWrapper>
  </Authenticated>
</template>

<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { trans as __ } from 'laravel-vue-i18n'
import Authenticated from '@/layouts/Authenticated.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppLink from '@/components/AppLink.vue'
import ConfirmButton from '@/components/ConfirmButton.vue'
import { Table, Thead, Th, Tbody, Td, ActionColumn } from '@/components/tables/index.js'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  students: { type: Array, default: () => [] },
  otherStaff: { type: Array, default: () => [] },
  reservations: { type: Array, default: () => [] },
  bookingOpen: Boolean,
  opensAt: String,
  closesAt: String,
  myReservations: { type: Array, default: () => [] },
  openCount: Number,
  canManageTimeSlots: Boolean,
  schoolStats: Object,
})
const user = usePage().props.user
const { displayDate } = useDates()

const stats = computed(() => props.schoolStats ? [
  { label: __('Upcoming time slots'), value: props.schoolStats.slots },
  { label: __('Reserved'), value: props.schoolStats.reserved },
  ...props.schoolStats.translators.map(t => ({
    label: __(':language translators', { language: t.language }),
    value: t.max ? `${t.used} / ${t.max}` : `${t.used}`,
  })),
] : [])

const teacherRows = (student) => {
  const rows = student.sections.flatMap(section => [section.teacher, section.alt_teacher]
    .filter(Boolean)
    .map(teacher => ({
      key: `${section.id}-${teacher.id}`,
      course: section.course?.name,
      teacher,
      bookable: section.can_book && section.course?.can_book,
    })))
  props.otherStaff.forEach(staff => rows.push({ key: `staff-${staff.id}`, course: '', teacher: staff, bookable: true }))
  return rows
}
const reservationFor = (student, teacher) => props.reservations.find(r => r.student_id === student.id && r.user?.id === teacher.id)
const cancel = (slot, close) => router.delete(`/reservations/${slot.id}`, { onFinish: close, preserveScroll: true })
</script>
