<template>
  <Authenticated>
    <template #actions>
      <AppButton component="a" :href="exportUrl" color="white">{{ __('Export CSV') }}</AppButton>
    </template>

    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div v-for="language in capacity" :key="language.value" class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm px-5 py-4">
        <dt class="text-sm text-gray-500 dark:text-gray-300 truncate">{{ language.label }}</dt>
        <dd class="text-2xl font-semibold">
          {{ language.used }}<span v-if="language.request_max" class="text-base font-normal text-gray-500 dark:text-gray-300"> / {{ language.request_max }}</span>
        </dd>
        <dd v-if="language.overlap_max" class="text-xs text-gray-500 dark:text-gray-300">{{ __(':count at a time', { count: language.overlap_max }) }}</dd>
      </div>
    </dl>

    <form @submit.prevent="apply" class="mb-4 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
      <FormField class="col-span-2">
        <template #component>
          <div class="flex flex-wrap gap-3">
            <AppCheckbox v-for="language in capacity" :key="language.value" v-model="form.language" :value="language.value">{{ language.label }}</AppCheckbox>
          </div>
        </template>
        {{ __('Language') }}
      </FormField>
      <FormField v-model="form.from" type="date">{{ __('From') }}</FormField>
      <FormField v-model="form.to" type="date">{{ __('To') }}</FormField>
      <div class="flex gap-2">
        <AppButton type="submit">{{ __('Filter') }}</AppButton>
        <AppButton type="button" color="white" @click="reset">{{ __('Reset') }}</AppButton>
      </div>
    </form>

    <Table>
      <Thead>
        <tr>
          <Th>{{ __('When') }}</Th>
          <Th>{{ __('Language') }}</Th>
          <Th>{{ __('Staff') }}</Th>
          <Th>{{ __('Student') }}</Th>
          <Th>{{ __('Contact') }}</Th>
          <Th>{{ __('Where') }}</Th>
          <Th>{{ __('Notes') }}</Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-if="requests.length === 0">
          <Td colspan="7">{{ __('No translator requests.') }}</Td>
        </tr>
        <tr v-for="slot in requests" :key="slot.id">
          <Td class="whitespace-nowrap">{{ displayDate(slot.starts_at, 'full') }}</Td>
          <Td>{{ labelFor(slot.language) }}</Td>
          <Td>{{ slot.user?.name }}</Td>
          <Td>{{ slot.student?.name }}</Td>
          <Td>
            <div>{{ slot.reserved_by?.name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-300">{{ slot.reserved_by?.email }}</div>
          </Td>
          <Td>{{ slot.requested_online || slot.is_online ? __('Online') : (slot.location || '—') }}</Td>
          <Td class="max-w-xs">
            <div v-if="slot.contact_notes" class="truncate">{{ slot.contact_notes }}</div>
            <div v-if="slot.translator_notes" class="truncate text-xs text-gray-500 dark:text-gray-300">{{ slot.translator_notes }}</div>
          </Td>
        </tr>
      </Tbody>
    </Table>
  </Authenticated>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import Authenticated from '@/layouts/Authenticated.vue'
import { Table, Thead, Th, Tbody, Td } from '@/components/tables/index.js'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppButton from '@/components/AppButton.vue'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  requests: Array,
  filters: Object,
  capacity: Array,
})
const { displayDate } = useDates()
const form = reactive({
  language: props.filters.language ?? [],
  from: props.filters.from ?? null,
  to: props.filters.to ?? null,
})
const query = () => Object.fromEntries(Object.entries(form).filter(([, v]) => Array.isArray(v) ? v.length : v))
const apply = () => router.get('/translators', query(), { preserveState: true })
const reset = () => {
  form.language = []
  form.from = null
  form.to = null
  apply()
}
const exportUrl = computed(() => '/translators?' + new URLSearchParams({ ...Object.fromEntries(Object.entries(query()).filter(([k]) => k !== 'language')), export: 'csv' }).toString()
  + form.language.map(l => `&language[]=${encodeURIComponent(l)}`).join(''))
const labelFor = value => props.capacity.find(l => l.value === value)?.label ?? value
</script>
