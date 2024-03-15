<template>
  <AppFieldset>
    <FormField v-model="form.teacher_notes" component="AppTextarea" :help="__(`This will be shown to the contacts when viewing the time slot.`)">
      {{ __('Teacher notes') }}
    </FormField>

    <FormField v-if="!form.is_online" v-model="form.location" :help="__(`The physical location of the meeting, i.e. a room number. If empty, it will be the teacher's configured room number.`)">
      {{ __('Location') }}
    </FormField>

    <FormField v-if="school.allow_online_meetings" :help="__(`Indicates whether this time slot can be requested to be online.`)">
      <template #component>
        <AppCheckbox v-model="form.allow_online_meetings">
          {{ __('Allow online meetings') }}
        </AppCheckbox>
      </template>
    </FormField>

    <FormField v-if="school.allow_online_meetings && form.allow_online_meetings" :help="__(`Indicates whether this time slot is online-only.`)">
      <template #component>
        <AppCheckbox v-model="form.is_online">
          {{ __('Online meeting') }}
        </AppCheckbox>
      </template>
    </FormField>

    <FormField v-if="school.allow_online_meetings && form.is_online" v-model="form.meeting_url" :error="form.errors.meeting_url">
      {{ __('Meeting URL') }}
    </FormField>

    <FormField :help="__(`If disabled, only admins can reserve time slots for students and contacts.`)">
      <template #component>
        <AppCheckbox v-model="form.contact_can_book">
          {{ __('Allow contacts to reserve the time slot') }}
        </AppCheckbox>
      </template>
    </FormField>

    <FormField v-if="school.allow_translator_requests" :help="__(`Allow contacts to request a translator based on the school's configuration.`)">
      <template #component>
        <AppCheckbox v-model="form.allow_translator_requests">
          {{ __('Allow translator request') }}
        </AppCheckbox>
      </template>
    </FormField>
  </AppFieldset>
</template>

<script setup>
import AppFieldset from '@/components/forms/AppFieldset.vue'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'

const props = defineProps({
  modelValue: Object,
  school: Object,
})
const emit = defineEmits(['update:modelValue'])
const form = useVModel(props, 'modelValue', emit)
</script>
