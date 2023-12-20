<template>
  <form @submit.prevent="save">
    <SplitForm :loading="form.processing">
      <template #headline>
        <Headline3>{{ __('Time slot settings') }}</Headline3>
        <HelpText>{{ __("Configure your school's settings for time slots.") }}</HelpText>
      </template>

      <FormField :error="form.errors.timezone" :help="__('When a user has not set their timezone, it will use this as their timezone.')" required class="col-span-6 sm:col-span-3">
        {{ __("School's default timezone") }}
        <template #component="{ id, hasError }">
          <TimezoneCombobox v-model="form.timezone" :id="id" :has-error="hasError" />
        </template>
      </FormField>

      <FormField v-model="form.booking_buffer_hours" :error="form.errors.booking_buffer_hours" type="number" :help="__('The number of hours before a time slot that a contact cannot book it.')" required class="col-span-6 sm:col-span-3">
        {{ __('Booking buffer') }}
      </FormField>

      <FormField :error="form.errors.allow_online_meetings" :help="__('When enabled, teachers can add a meeting URL and contacts can request to meet online.')" class="col-span-6">
        <template #component>
          <AppCheckbox v-model="form.allow_online_meetings">
            {{ __('Allow online meetings') }}
          </AppCheckbox>
        </template>
      </FormField>

      <FormField :error="form.errors.allow_translator_requests" :help="__('When enabled, contacts can request a translator and provide the language for which they need a translator.')" class="col-span-6">
        <template #component>
          <AppCheckbox v-model="form.allow_translator_requests">
            {{ __('Allow contacts to request a translator') }}
          </AppCheckbox>
        </template>
      </FormField>

      <SimpleAlert class="col-span-6">
        {{ __('Times are based in the :timezone timezone.', { timezone: form.timezone || 'UTC' }) }}
      </SimpleAlert>

      <div class="col-span-6">
        <div class="grid grid-cols-6 gap-6">
          <FormField :error="form.errors.open_for_contacts_at" class="col-span-6 sm:col-span-3 sm:col-start-1">
            {{ __('Open booking for contacts') }}
            <template #component="{ id, hasError }">
              <AppDatepicker
                v-model="form.open_for_contacts_at"
                mode="dateTime"
                :id="id"
                :has-error="hasError"
              />
            </template>
          </FormField>

          <FormField :error="form.errors.close_for_contacts_at" class="col-span-6 sm:col-span-3">
            {{ __('Close booking for contacts') }}
            <template #component="{ id, hasError }">
              <AppDatepicker
                v-model="form.close_for_contacts_at"
                mode="dateTime"
                :id="id"
                :has-error="hasError"
              />
            </template>
          </FormField>
        </div>

        <SimpleAlert :level="openForContacts ? 'success' : 'warning'" class="mt-4" not-dismissible>
          {{ openForContacts ? __('Contacts can reserve time slots.') : __('Contacts cannot reserve time slots.') }}
        </SimpleAlert>
      </div>

      <div class="col-span-6">
        <div class="grid grid-cols-6 gap-6">
          <FormField :error="form.errors.open_for_teachers_at" class="col-span-6 sm:col-span-3 sm:col-start-1">
            {{ __('Open slot editing for teachers') }}
            <template #component="{ id, hasError }">
              <AppDatepicker
                v-model="form.open_for_teachers_at"
                mode="dateTime"
                :id="id"
                :has-error="hasError"
              />
            </template>
          </FormField>

          <FormField :error="form.errors.close_for_teachers_at" class="col-span-6 sm:col-span-3">
            {{ __('Close slot editing for teachers') }}
            <template #component="{ id, hasError }">
              <AppDatepicker
                v-model="form.close_for_teachers_at"
                mode="dateTime"
                :id="id"
                :has-error="hasError"
              />
            </template>
          </FormField>
        </div>

        <SimpleAlert :level="openForTeachers ? 'success' : 'warning'" class="mt-4" not-dismissible>
          {{ openForTeachers ? __('Teachers can create and edit their time slots.') : __('Teachers cannot create and edit their time slots.') }}
        </SimpleAlert>
      </div>
    </SplitForm>
  </form>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import SplitForm from '@/components/SplitForm.vue'
import Headline3 from '@/components/Headline3.vue'
import HelpText from '@/components/forms/HelpText.vue'
import useDates from '@/composition/useDates.js'
import FormField from '@/components/forms/FormField.vue'
import TimezoneCombobox from '@/components/forms/TimezoneCombobox.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppDatepicker from '@/components/forms/AppDatepicker.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'

const props = defineProps({
  school: Object,
})
const { dayjs } = useDates()
const form = useForm({
  timezone: props.school.timezone,
  allow_online_meetings: props.school.allow_online_meetings,
  allow_translator_requests: props.school.allow_translator_requests,
  booking_buffer_hours: props.school.booking_buffer_hours,
  open_for_contacts_at: props.school.open_for_contacts_at,
  close_for_contacts_at: props.school.close_for_contacts_at,
  open_for_teachers_at: props.school.open_for_teachers_at,
  close_for_teachers_at: props.school.close_for_teachers_at,
})

const save = () => {
  form.put('/settings/school', {
    preserveScroll: true,
  })
}
const openForContacts = computed(() => {
  if (!form.open_for_contacts_at && !form.close_for_contacts_at) {
    return false
  }

  const now = dayjs()

  return (!form.open_for_contacts_at || now >= dayjs(form.open_for_contacts_at))
    && (!form.close_for_contacts_at || now <= dayjs(form.close_for_contacts_at))
})
const openForTeachers = computed(() => {
  if (!form.open_for_teachers_at && !form.close_for_teachers_at) {
    return false
  }

  const now = dayjs()

  return (!form.open_for_teachers_at || now >= dayjs(form.open_for_teachers_at))
    && (!form.close_for_teachers_at || now <= dayjs(form.close_for_teachers_at))
})
</script>
