<template>
  <Authenticated>
    <template #actions>
      <AppButton v-if="can('time_slot.update')" @click="openForm()">{{ __('Add translator') }}</AppButton>
    </template>

    <Table>
      <Thead>
        <tr>
          <Th>{{ __('Name') }}</Th>
          <Th>{{ __('Languages') }}</Th>
          <Th>{{ __('Contact') }}</Th>
          <Th>{{ __('Upcoming') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-if="translators.length === 0">
          <Td colspan="5">{{ __('No translators yet.') }}</Td>
        </tr>
        <tr v-for="translator in translators" :key="translator.id">
          <Td>
            <div class="flex items-center gap-2">
              <span>{{ translator.name }}</span>
              <Pill v-if="!translator.active" color="yellow" size="sm">{{ __('Inactive') }}</Pill>
            </div>
          </Td>
          <Td>
            <div class="flex flex-wrap gap-1">
              <Pill v-for="label in translator.language_labels" :key="label" size="sm">{{ label }}</Pill>
            </div>
          </Td>
          <Td>
            <div>{{ translator.email }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-300">{{ translator.phone }}</div>
          </Td>
          <Td>{{ translator.upcoming_count }}</Td>
          <ActionColumn>
            <div v-if="can('time_slot.update')" class="flex gap-3 justify-end items-center">
              <TableLink is="a" :href="`/translators?translator_id=${translator.id}&export=csv`">{{ __('Schedule CSV') }}</TableLink>
              <TableLink is="button" type="button" @click="openForm(translator)">{{ __('Edit') }}</TableLink>
              <ConfirmButton color="red" class="text-sm" @confirmed="close => remove(translator, close)">
                {{ __('Remove') }}
                <template #actionText>{{ __('Remove translator') }}</template>
              </ConfirmButton>
            </div>
          </ActionColumn>
        </tr>
      </Tbody>
    </Table>

    <Modal v-if="editing" ref="modal" @close="editing = null" :headline="editing.id ? __('Edit translator') : __('Add translator')" :action-loading="form.processing" @action="save">
      <form @submit.prevent="save" class="grid grid-cols-6 gap-4">
        <FormField v-model="form.name" :error="form.errors.name" required class="col-span-6">{{ __('Name') }}</FormField>
        <FormField v-model="form.email" :error="form.errors.email" type="email" class="col-span-6 sm:col-span-3">{{ __('Email') }}</FormField>
        <FormField v-model="form.phone" :error="form.errors.phone" class="col-span-6 sm:col-span-3">{{ __('Phone') }}</FormField>
        <FormField :error="form.errors.languages" required class="col-span-6">
          <template #component>
            <div v-if="languages.length" class="flex flex-wrap gap-3">
              <AppCheckbox v-for="language in languages" :key="language.value" v-model="form.languages" :value="language.value">{{ language.label }}</AppCheckbox>
            </div>
            <HelpText v-else>
              {{ __('No languages are configured for this school yet.') }}
              <AppLink href="/settings/school/edit">{{ __('Add languages in school settings') }}</AppLink>
            </HelpText>
          </template>
          {{ __('Languages') }}
        </FormField>
        <FormField v-model="form.notes" :error="form.errors.notes" component="AppTextarea" class="col-span-6">{{ __('Notes') }}</FormField>
        <FormField class="col-span-6">
          <template #component>
            <AppCheckbox v-model="form.active">{{ __('Active') }}</AppCheckbox>
          </template>
        </FormField>
      </form>
    </Modal>
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Authenticated from '@/layouts/Authenticated.vue'
import { Table, Thead, Th, Tbody, Td, ActionColumn, TableLink } from '@/components/tables/index.js'
import Modal from '@/components/modals/Modal.vue'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppButton from '@/components/AppButton.vue'
import ConfirmButton from '@/components/ConfirmButton.vue'
import Pill from '@/components/Pill.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  translators: Array,
  languages: Array,
})
const modal = ref()
const editing = ref(null)
const form = useForm({
  name: '',
  email: '',
  phone: '',
  notes: '',
  languages: [],
  active: true,
})
const openForm = (translator = {}) => {
  form.defaults({
    name: translator.name ?? '',
    email: translator.email ?? '',
    phone: translator.phone ?? '',
    notes: translator.notes ?? '',
    languages: [...(translator.languages ?? [])],
    active: translator.active ?? true,
  }).reset()
  form.clearErrors()
  editing.value = translator
}
const save = () => {
  const options = { preserveScroll: true, onSuccess: () => modal.value.close() }
  editing.value.id
    ? form.put(`/translator-profiles/${editing.value.id}`, options)
    : form.post('/translator-profiles', options)
}
const remove = (translator, close) => router.delete(`/translator-profiles/${translator.id}`, { preserveScroll: true, onFinish: close })
</script>
