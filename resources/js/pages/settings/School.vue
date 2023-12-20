<template>
  <Authenticated>
    <template #actions>
      <ActionWrapper>
        <AppButton size="sm" :loading="syncing" @click.prevent="sync()">
          {{ __('Sync') }}
        </AppButton>
      </ActionWrapper>
    </template>

    <Spacer>
      <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <ActionStat
          v-for="item in counts"
          :key="item.key"
          :value="item.value"
          :loading="uiState === item.key"
          :action-text="__('Sync')"
          :icon="UsersIcon"
          @action="syncItem(item.key)"
        >
          {{ item.label }}
        </ActionStat>
      </dl>

      <form @submit.prevent="saveTimeSlotSettings">
        <SplitForm :loading="timeSlotForm.processing">
          <template #headline>
            <Headline3>{{ __('Time slot settings') }}</Headline3>
            <HelpText>{{ __("Configure your school's settings for time slots.") }}</HelpText>
          </template>

          <FormField :error="timeSlotForm.errors.timezone" :help="__('When a user has not set their timezone, it will use this as their timezone.')" required class="col-span-6 sm:col-span-3">
            {{ __("School's default timezone") }}
            <template #component="{ id, hasError }">
              <TimezoneCombobox v-model="timeSlotForm.timezone" :id="id" :has-error="hasError" />
            </template>
          </FormField>

          <FormField v-model="timeSlotForm.booking_buffer_hours" :error="timeSlotForm.errors.booking_buffer_hours" type="number" :help="__('The number of hours before a time slot that a contact cannot book it.')" required class="col-span-6 sm:col-span-3">
            {{ __('Booking buffer') }}
          </FormField>

          <FormField :error="timeSlotForm.errors.allow_online_meetings" :help="__('When enabled, teachers can add a meeting URL and contacts can request to meet online.')" class="col-span-6">
            <template #component>
              <AppCheckbox v-model="timeSlotForm.allow_online_meetings">
                {{ __('Allow online meetings') }}
              </AppCheckbox>
            </template>
          </FormField>

          <FormField :error="timeSlotForm.errors.allow_translator_requests" :help="__('When enabled, contacts can request a translator and provide the language for which they need a translator.')" class="col-span-6">
            <template #component>
              <AppCheckbox v-model="timeSlotForm.allow_translator_requests">
                {{ __('Allow contacts to request a translator') }}
              </AppCheckbox>
            </template>
          </FormField>

          <SimpleAlert class="col-span-6">
            {{ __('Times are based in the :timezone timezone.', { timezone: timeSlotForm.timezone || 'UTC' }) }}
          </SimpleAlert>

          <div class="col-span-6">
            <div class="grid grid-cols-6 gap-6">
              <FormField :error="timeSlotForm.errors.open_for_contacts_at" class="col-span-6 sm:col-span-3 sm:col-start-1">
                {{ __('Open booking for contacts') }}
                <template #component="{ id, hasError }">
                  <AppDatepicker
                    v-model="timeSlotForm.open_for_contacts_at"
                    mode="dateTime"
                    :id="id"
                    :has-error="hasError"
                  />
                </template>
              </FormField>

              <FormField :error="timeSlotForm.errors.close_for_contacts_at" class="col-span-6 sm:col-span-3">
                {{ __('Close booking for contacts') }}
                <template #component="{ id, hasError }">
                  <AppDatepicker
                    v-model="timeSlotForm.close_for_contacts_at"
                    mode="dateTime"
                    :id="id"
                    :has-error="hasError"
                  />
                </template>
              </FormField>
            </div>

            <SimpleAlert :level="openForContacts ? 'success' : 'warning'" class="mt-4" not-dismissible>
              {{ openForContacts ? __('Contacts can book time slots.') : __('Contacts cannot book time slots.') }}
            </SimpleAlert>
          </div>

          <div class="col-span-6">
            <div class="grid grid-cols-6 gap-6">
              <FormField :error="timeSlotForm.errors.open_for_teachers_at" class="col-span-6 sm:col-span-3 sm:col-start-1">
                {{ __('Open slot editing for teachers') }}
                <template #component="{ id, hasError }">
                  <AppDatepicker
                    v-model="timeSlotForm.open_for_teachers_at"
                    mode="dateTime"
                    :id="id"
                    :has-error="hasError"
                  />
                </template>
              </FormField>

              <FormField :error="timeSlotForm.errors.close_for_teachers_at" class="col-span-6 sm:col-span-3">
                {{ __('Close slot editing for teachers') }}
                <template #component="{ id, hasError }">
                  <AppDatepicker
                    v-model="timeSlotForm.close_for_teachers_at"
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
    </Spacer>
  </Authenticated>
</template>

<script setup>
import { computed, ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import AppButton from '@/components/AppButton.vue'
import ActionWrapper from '@/components/ActionWrapper.vue'
import useSisObjectSync from '@/composition/useSisObjectSync.js'
import ActionStat from '@/components/ActionStat.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'
import { router, useForm } from '@inertiajs/vue3'
import Spacer from '@/components/Spacer.vue'
import SplitForm from '@/components/SplitForm.vue'
import Headline3 from '@/components/Headline3.vue'
import HelpText from '@/components/forms/HelpText.vue'
import useDates from '@/composition/useDates.js'
import FormField from '@/components/forms/FormField.vue'
import TimezoneCombobox from '@/components/forms/TimezoneCombobox.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppDatepicker from '@/components/forms/AppDatepicker.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import FadeIn from '@/components/transitions/FadeIn.vue'

const props = defineProps({
  school: Object,
  counts: Object,
})
const { syncing, sync } = useSisObjectSync('school', props.school)
const { dayjs } = useDates()
const uiState = ref()
const timeSlotForm = useForm({
  timezone: props.school.timezone,
  allow_online_meetings: props.school.allow_online_meetings,
  allow_translator_requests: props.school.allow_translator_requests,
  booking_buffer_hours: props.school.booking_buffer_hours,
  open_for_contacts_at: props.school.open_for_contacts_at,
  close_for_contacts_at: props.school.close_for_contacts_at,
  open_for_teachers_at: props.school.open_for_teachers_at,
  close_for_teachers_at: props.school.close_for_teachers_at,
})

const saveTimeSlotSettings = () => {
  timeSlotForm.put('/settings/school', {
    preserveScroll: true,
  })
}
const syncItem = userType => {
  uiState.value = userType

  try {
    router.post(`/settings/school/sync/${userType}`, null, {
      preserveScroll: true,
      onFinish: () => {
        uiState.value = null
      },
    })
  } catch (err) {
    console.error(err)
    uiState.value = null
  }
}
const openForContacts = computed(() => {
  if (!timeSlotForm.open_for_contacts_at && !timeSlotForm.close_for_contacts_at) {
    return false
  }

  const now = dayjs()

  return (!timeSlotForm.open_for_contacts_at || now >= dayjs(timeSlotForm.open_for_contacts_at))
    && (!timeSlotForm.close_for_contacts_at || now <= dayjs(timeSlotForm.close_for_contacts_at))
})
const openForTeachers = computed(() => {
  if (!timeSlotForm.open_for_teachers_at && !timeSlotForm.close_for_teachers_at) {
    return false
  }

  const now = dayjs()

  return (!timeSlotForm.open_for_teachers_at || now >= dayjs(timeSlotForm.open_for_teachers_at))
    && (!timeSlotForm.close_for_teachers_at || now <= dayjs(timeSlotForm.close_for_teachers_at))
})
</script>
