<template>
  <Authenticated>
    <CardWrapper class="mb-6">
      <CardPadding>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2 gap-6">
          <div>
            <CardHeader>{{ __('Default settings') }}</CardHeader>
            <HelpText>{{ __(`These settings will apply to all the time slots created.`) }}</HelpText>
          </div>
          <FadeIn>
            <AppFieldset v-show="showForm">
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

              <FormField v-if="school.allow_online_meetings && form.is_online" v-model="form.location">
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
          </FadeIn>
        </div>
      </CardPadding>
      <CardAction>
        <AppButton type="button" @click.prevent="showForm = !showForm">
          {{ showForm ? __('Hide time slot details') : __('Show time slot details') }}
        </AppButton>
      </CardAction>
    </CardWrapper>

    <CardWrapper>
      <CardPadding>
        <SimpleAlert class="mb-5">
          {{ __('All times are based on the school timezone, :timezone.', { timezone: school.timezone }) }}
        </SimpleAlert>

        <CreateTimeSlotCalendar
          :time-format="user.fc_time_format"
          :timezone="school.timezone"
          @select="onSelect"
        />
      </CardPadding>
    </CardWrapper>
  </Authenticated>
</template>

<script setup>
import { reactive, ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import useTimeSlots from '@/composition/useTimeSlots.js'
import clone from 'just-clone'
import AppFieldset from '@/components/forms/AppFieldset.vue'
import FormField from '@/components/forms/FormField.vue'
import AppTextarea from '@/components/forms/AppTextarea.vue'
import CardHeader from '@/components/CardHeader.vue'
import HelpText from '@/components/forms/HelpText.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import useModelSelection from '@/composition/useModelSelection.js'
import CreateTimeSlotCalendar from '@/components/CreateTimeSlotCalendar.vue'
import CardAction from '@/components/CardAction.vue'
import AppButton from '@/components/AppButton.vue'
import FadeIn from '@/components/transitions/FadeIn.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'

const props = defineProps({
  school: Object,
  user: Object,
})
const emit = defineEmits([])
const { timeSlotBase, createTimeSlot } = useTimeSlots()
const form = reactive(clone(timeSlotBase))
const { selection } = useModelSelection('user')
const showForm = ref(true)
const onSelect = fcEvent => {
  createTimeSlot(fcEvent, form)
}
</script>
