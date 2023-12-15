<template>
  <Authenticated>
    <Table>
      <Thead>
        <tr>
          <Th>{{ __('Number') }}</Th>
          <Th>{{ __('Grade') }}</Th>
          <Th>{{ __('Name') }}</Th>
          <Th>{{ __('Email') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="student in students.data" :key="student.id">
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
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import { ActionColumn, Table, Tbody, Th, Thead } from '@/components/tables/index.js'
import Td from '@/components/tables/Td.vue'
import Pagination from '@/components/tables/Pagination.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  students: Object,
})
</script>
