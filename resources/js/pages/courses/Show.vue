<template>
  <Authenticated>
    <template #actions>
      <DropDownButton>
        {{ __('Actions') }}
        <template #dropdown>
          <CourseActionMenu :course="course" />
        </template>
      </DropDownButton>
    </template>
    <template #after-title>
      <BookingDisabledPill v-if="!course.can_book" />
    </template>

    <div class="space-y-6 relative z-0">
      <SimpleAlert v-if="course.sections.length === 0">
        {{ __("This course doesn't have any sections.") }}
      </SimpleAlert>

      <CardWrapper v-for="section in course.sections" :key="section.id">
        <CardPadding class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <CardHeader>{{ __('Section :number', { number: section.section_number }) }}</CardHeader>
              <BookingDisabledPill v-if="!section.can_book" />
            </div>
            <div class="flex items-center gap-2">
              <div class="text-sm">{{ section.teacher_display }}</div>
              <BookingDisabledPill v-if="!section.teacher_can_book" />
            </div>
          </div>

          <DropDownButton>
            {{ __('Actions') }}
            <template #dropdown>
              <SectionActionMenu :section="section" />
            </template>
          </DropDownButton>
        </CardPadding>

        <StudentsTable :students="section.students" no-top-radius />
      </CardWrapper>
    </div>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import DropDownButton from '@/components/forms/buttons/DropDownButton.vue'
import CourseActionMenu from '@/components/actions/CourseActionMenu.vue'
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import AppButton from '@/components/AppButton.vue'
import SectionActionMenu from '@/components/actions/SectionActionMenu.vue'
import StudentsTable from '@/components/tables/StudentsTable.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'

const props = defineProps({
  course: Object,
})
</script>
