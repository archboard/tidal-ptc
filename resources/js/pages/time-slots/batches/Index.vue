<template>
  <Authenticated>
    <Table>
      <Thead>
        <tr>
          <Th>{{ __('Created') }}</Th>
          <Th>{{ __('Creator') }}</Th>
          <Th>{{ __('Time slots') }}</Th>
          <Th>{{ __('Teachers') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="batch in batches.data" :key="batch.id">
          <Td>{{ batch.created_at }}</Td>
          <Td>{{ batch.user?.name }}</Td>
          <Td>{{ batch.time_slots_count }}</Td>
          <Td>{{ batch.users_count }}</Td>
          <ActionColumn>
            <AppLink :href="`/batches/${batch.id}/edit`">{{ __('Edit') }}</AppLink>
          </ActionColumn>
        </tr>
        <tr v-if="batches.data.length === 0">
          <Td colspan="5">
            {{ __('No batches have been created yet.') }}
          </Td>
        </tr>
      </Tbody>
    </Table>
    <Pagination :links="batches.links" :meta="batches.meta" />
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import { Table, Thead, Th, Tbody, Td, ActionColumn, Pagination } from '@/components/tables/index.js'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  batches: Object,
})
const emit = defineEmits([])
</script>
