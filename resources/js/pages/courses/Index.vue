<template>
  <Authenticated>
    <Table>
      <Thead>
        <tr>
          <Th>{{ __('Name') }}</Th>
          <Th>{{ __('Number') }}</Th>
          <Th>{{ __('Sections') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="course in courses.data" :key="course.id">
          <Td>{{ course.name }}</Td>
          <Td>{{ course.course_number }}</Td>
          <Td>{{ course.sections_count }}</Td>
          <ActionColumn>
            <AppLink :href="`/courses/${course.id}`">
              {{ __('View') }}
            </AppLink>
          </ActionColumn>
        </tr>
      </Tbody>
    </Table>

    <Pagination :links="courses.links" :meta="courses.meta" />
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import { ActionColumn, Table, Tbody, Th, Thead } from '@/components/tables/index.js'
import Td from '@/components/tables/Td.vue'
import Pagination from '@/components/tables/Pagination.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  courses: Object,
})
</script>
