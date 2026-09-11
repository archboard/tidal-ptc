<template>
  <Authenticated>
    <form @submit.prevent="apply" class="mb-4 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
      <FormField class="col-span-1">
        <template #component>
          <AppSelect v-model="form.event" hide-null>
            <option :value="null">{{ __('Any event') }}</option>
            <option v-for="e in events" :key="e" :value="e">{{ e.replaceAll("_", " ") }}</option>
          </AppSelect>
        </template>
        {{ __('Event') }}
      </FormField>
      <FormField class="col-span-1">
        <template #component>
          <AppSelect v-model="form.subject_type" hide-null>
            <option :value="null">{{ __('Any subject') }}</option>
            <option v-for="t in subjectTypes" :key="t" :value="t">{{ t.replaceAll("_", " ") }}</option>
          </AppSelect>
        </template>
        {{ __('Subject') }}
      </FormField>
      <FormField v-model="form.from" type="date" class="col-span-1">{{ __('From') }}</FormField>
      <FormField v-model="form.to" type="date" class="col-span-1">{{ __('To') }}</FormField>
      <div class="flex gap-2">
        <AppButton type="submit">{{ __('Filter') }}</AppButton>
        <AppButton type="button" color="white" @click="reset">{{ __('Reset') }}</AppButton>
      </div>
    </form>

    <Table>
      <Thead>
        <tr>
          <Th>{{ __('When') }}</Th>
          <Th>{{ __('Who') }}</Th>
          <Th>{{ __('Event') }}</Th>
          <Th>{{ __('Subject') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="activity in activities.data" :key="activity.id">
          <Td class="whitespace-nowrap">{{ activity.created_at }}</Td>
          <Td>{{ activity.causer ?? __('System') }}</Td>
          <Td>{{ activity.description }}</Td>
          <Td>
            <span class="text-gray-500 text-xs uppercase">{{ activity.subject_type }}</span>
            <div v-if="activity.subject">{{ activity.subject }}</div>
          </Td>
          <ActionColumn>
            <TableLink is="button" type="button" @click="selected = activity">{{ __('View details') }}</TableLink>
          </ActionColumn>
        </tr>
        <tr v-if="activities.data.length === 0">
          <Td colspan="5">{{ __('No activity recorded yet.') }}</Td>
        </tr>
      </Tbody>
    </Table>
    <Pagination :links="activities.links" :meta="activities.meta" />

    <ActivityDetailsModal v-if="selected" :activity="selected" @close="selected = null" />
  </Authenticated>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Authenticated from '@/layouts/Authenticated.vue'
import { Table, Thead, Th, Tbody, Td, Pagination, ActionColumn, TableLink } from '@/components/tables/index.js'
import FormField from '@/components/forms/FormField.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import AppButton from '@/components/AppButton.vue'
import ActivityDetailsModal from '@/components/modals/ActivityDetailsModal.vue'

const props = defineProps({
  activities: Object,
  filters: Object,
  events: Array,
  subjectTypes: Array,
})
const selected = ref(null)
const form = reactive({
  event: props.filters.event ?? null,
  subject_type: props.filters.subject_type ?? null,
  from: props.filters.from ?? null,
  to: props.filters.to ?? null,
})
const apply = () => router.get('/activity', Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true })
const reset = () => {
  Object.keys(form).forEach(k => form[k] = null)
  apply()
}
</script>
