<template>
  <Authenticated>
    <Table>
      <Thead>
        <tr>
          <Th>{{ __('Course') }}</Th>
          <Th>{{ __('Course number') }}</Th>
          <Th>{{ __('Section') }}</Th>
          <Th>{{ __('Enrollment') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="section in sections.data" :key="section.id">
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

const props = defineProps({
  sections: Object,
})
</script>
