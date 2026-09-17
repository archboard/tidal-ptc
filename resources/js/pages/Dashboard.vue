<template>
  <Authenticated>
    <!-- Guardian -->
    <div v-if="user.user_type === UserType.guardian" class="space-y-6">
      <CardWrapper v-if="!bookingOpen">
        <CardPadding>
          <CardHeader>{{ __('Booking is closed') }}</CardHeader>
          <HelpText v-if="opensAt">{{ __('Booking opens :when.', { when: displayDate(opensAt, 'full', true) }) }}</HelpText>
        </CardPadding>
      </CardWrapper>

      <SimpleAlert v-else-if="closesAt" level="neutral" not-dismissible>{{ __('Booking closes :when.', { when: displayDate(closesAt, 'full', true) }) }}</SimpleAlert>

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
              <Th>{{ __('Conference') }}</Th>
              <Th></Th>
            </tr>
          </Thead>
          <Tbody>
            <tr v-if="teacherRows(student).length === 0">
              <Td colspan="4" class="text-center text-gray-500 dark:text-gray-400">{{ __('No teachers found for this student.') }}</Td>
            </tr>
            <tr v-for="row in teacherRows(student)" :key="row.key">
              <Td>{{ row.courses.filter(Boolean).join(', ') }}</Td>
              <Td class="whitespace-nowrap">{{ row.label }}</Td>
              <Td v-if="reservationFor(student, row.teacher)" class="whitespace-nowrap">
                {{ reservationFor(student, row.teacher).range_display }}
                <span v-if="reservationFor(student, row.teacher).requested_online || reservationFor(student, row.teacher).is_online" class="text-gray-500 dark:text-gray-400"> · {{ __('Online') }}</span>
                <span v-else-if="reservationFor(student, row.teacher).location" class="text-gray-500 dark:text-gray-400"> · {{ reservationFor(student, row.teacher).location }}</span>
              </Td>
              <Td v-else class="whitespace-nowrap text-gray-500 dark:text-gray-400">{{ __('Not booked') }}</Td>
              <ActionColumn class="whitespace-nowrap">
                <template v-if="reservationFor(student, row.teacher)">
                  <AppLink v-if="bookingOpen" is="button" type="button" @click="booking = { student, staff: row.teacher }">{{ __('Move') }}</AppLink>
                  <ConfirmButton link color="red" @confirmed="close => cancel(reservationFor(student, row.teacher), close)">
                    {{ __('Cancel') }}
                    <template #actionText>{{ __('Cancel conference') }}</template>
                  </ConfirmButton>
                </template>
                <template v-else-if="bookingOpen && student.can_book && row.bookable">
                  <AppLink v-if="availabilityFor(row.teacher) === 'open'" is="button" type="button" @click="booking = { student, staff: row.teacher }">
                    {{ __('Book') }}
                  </AppLink>
                  <span v-else-if="availabilityFor(row.teacher) === 'full'" class="text-gray-500 dark:text-gray-400">{{ __('Fully booked') }}</span>
                  <span v-else class="text-gray-500 dark:text-gray-400">{{ __('No time slots yet') }}</span>
                </template>
              </ActionColumn>
            </tr>
          </Tbody>
        </Table>
      </CardWrapper>
    </div>

    <!-- Staff -->
    <div v-else-if="user.user_type === UserType.staff" class="space-y-6">
      <SimpleAlert :level="school.contacts_can_book ? 'success' : 'warning'" not-dismissible>
        <template v-if="school.contacts_can_book">{{ __('Booking is open.') }}</template>
        <template v-else-if="school.open_for_contacts_at && dayjs(school.open_for_contacts_at).isAfter(dayjs())">{{ __('Booking will be available on :when.', { when: displayDate(school.open_for_contacts_at, 'full', true) }) }}</template>
        <template v-else>{{ __('Booking is closed.') }}</template>
      </SimpleAlert>

      <SchoolTimeSlotSettings v-if="canEditSchoolSettings" :school="school" compact />

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

  <BookConferenceModal v-if="booking" :student="booking.student" :staff="booking.staff" @close="booking = null" />
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { trans as __ } from 'laravel-vue-i18n'
import Authenticated from '@/layouts/Authenticated.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppLink from '@/components/AppLink.vue'
import ConfirmButton from '@/components/ConfirmButton.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import SchoolTimeSlotSettings from '@/components/forms/form-sets/SchoolTimeSlotSettings.vue'
import BookConferenceModal from '@/components/modals/BookConferenceModal.vue'
import { Table, Thead, Th, Tbody, Td, ActionColumn } from '@/components/tables/index.js'
import useDates from '@/composition/useDates.js'
import { UserType } from '@/Enums/UserType.enum.js'

const props = defineProps({
  students: { type: Array, default: () => [] },
  otherStaff: { type: Array, default: () => [] },
  slotAvailability: { type: Object, default: () => ({}) },
  reservations: { type: Array, default: () => [] },
  bookingOpen: Boolean,
  opensAt: String,
  closesAt: String,
  myReservations: { type: Array, default: () => [] },
  openCount: Number,
  canManageTimeSlots: Boolean,
  canEditSchoolSettings: Boolean,
  schoolStats: Object,
})
const user = usePage().props.user
const school = usePage().props.school
const { displayDate, dayjs } = useDates()

const stats = computed(() => props.schoolStats ? [
  { label: __('Upcoming time slots'), value: props.schoolStats.slots },
  { label: __('Reserved'), value: props.schoolStats.reserved },
  ...props.schoolStats.translators.map(t => ({
    label: __(':language translators', { language: t.language }),
    value: t.max ? `${t.used} / ${t.max}` : `${t.used}`,
  })),
] : [])

const teacherRows = (student) => {
  // ponytail: a conference is per teacher, not per section, so sections sharing a teacher collapse into one row
  const rows = new Map()
  student.sections.filter(section => section.teacher).forEach(section => {
    const teacher = section.alt_teacher ?? section.teacher
    const row = rows.get(teacher.id) ?? rows.set(teacher.id, {
      key: `teacher-${teacher.id}`,
      courses: [],
      teacher,
      label: section.teacher_display,
      bookable: false,
    }).get(teacher.id)
    row.courses.push(section.course?.name)
    row.bookable ||= section.can_book && section.course?.can_book
  })
  props.otherStaff.forEach(staff => rows.set(`staff-${staff.id}`, { key: `staff-${staff.id}`, courses: [], teacher: staff, label: staff.name, bookable: true }))
  return [...rows.values()]
}
const booking = ref(null)
const availabilityFor = teacher => props.slotAvailability[teacher.id] ?? 'none'
const reservationFor = (student, teacher) => props.reservations.find(r => r.student_id === student.id && r.user?.id === teacher.id)
const cancel = (slot, close) => router.delete(`/reservations/${slot.id}`, { onFinish: close, preserveScroll: true })
</script>
