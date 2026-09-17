<template>
  <div>
    <HelpText v-if="existingReservation">
      {{ __('Currently booked for :when. Pick a new time below to move it.', { when: displayDate(existingReservation.starts_at, 'full') }) }}
    </HelpText>
    <HelpText v-else>{{ __('Choose a time that works for you.') }}</HelpText>

    <p v-if="slots.length === 0" class="mt-4">{{ __('There are no available times right now.') }}</p>

    <div v-for="(daySlots, day) in slotsByDay" :key="day" class="mt-4">
      <h4 class="font-semibold mb-2">{{ day }}</h4>
      <div class="flex flex-wrap gap-2">
        <AppButton
          v-for="slot in daySlots"
          :key="slot.id"
          type="button"
          :color="selected?.id === slot.id ? 'primary' : 'white'"
          @click="selected = slot"
        >
          {{ displayDate(slot.starts_at, 'time') }} – {{ displayDate(slot.ends_at, 'time') }}
          <span v-if="slot.id === existingReservation?.id"> · {{ __('Current') }}</span>
        </AppButton>
      </div>
    </div>

    <form v-if="selected" @submit.prevent="submit" class="mt-6 space-y-4">
      <p class="font-medium">{{ selected.range_display }}</p>
      <p v-if="selected.location" class="text-sm">{{ __('Location') }}: {{ selected.location }}</p>

      <FormField v-model="form.contact_notes" :error="form.errors.contact_notes" component="AppTextarea" :help="__('Anything you would like to discuss?')">
        {{ __('Notes') }}
      </FormField>

      <FormField v-if="allowOnline && selected.allow_online_meetings" :error="form.errors.requested_online">
        <template #component>
          <AppCheckbox v-model="form.requested_online">{{ __('I would like to meet online') }}</AppCheckbox>
        </template>
      </FormField>

      <FormField v-if="languages.length && selected.allow_translator_requests" :error="form.errors.language" :help="__('Request a translator for this conference.')">
        <template #component>
          <AppSelect v-model="form.language" hide-null>
            <option :value="null">{{ __('No translator needed') }}</option>
            <option v-for="language in languages" :key="language.value" :value="language.value">{{ language.label }}</option>
          </AppSelect>
        </template>
        {{ __('Translator') }}
      </FormField>

      <FieldError v-if="form.errors.time_slot_id || form.errors.student_id">{{ form.errors.time_slot_id || form.errors.student_id }}</FieldError>

      <div class="flex gap-2">
        <AppButton v-if="!isCurrent" type="submit" :loading="form.processing">
          {{ existingReservation ? __('Move conference') : __('Book conference') }}
        </AppButton>
        <AppButton type="button" color="white" @click="selected = null">{{ __('Cancel') }}</AppButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import HelpText from '@/components/forms/HelpText.vue'
import FormField from '@/components/forms/FormField.vue'
import FieldError from '@/components/forms/FieldError.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import AppButton from '@/components/AppButton.vue'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  student: Object,
  slots: Array,
  existingReservation: Object,
  languages: Array,
  allowOnline: Boolean,
})
const emit = defineEmits(['success'])
const { displayDate } = useDates()
const selected = ref(props.existingReservation ?? null)
const isCurrent = computed(() => selected.value?.id === props.existingReservation?.id)
const form = useForm({
  student_id: props.student.id,
  contact_notes: props.existingReservation?.contact_notes ?? '',
  requested_online: props.existingReservation?.requested_online ?? false,
  language: props.existingReservation?.language ?? null,
})
const slotsByDay = computed(() => [...props.slots, ...(props.existingReservation ? [props.existingReservation] : [])]
  .sort((a, b) => a.starts_at.localeCompare(b.starts_at))
  .reduce((carry, slot) => {
    const day = displayDate(slot.starts_at, 'dddd, MMMM D')
    ;(carry[day] ??= []).push(slot)
    return carry
  }, {}))
const submit = () => {
  const options = { onSuccess: () => emit('success') }
  if (props.existingReservation) {
    form.transform(data => ({ ...data, time_slot_id: selected.value.id }))
      .put(`/reservations/${props.existingReservation.id}`, options)
  } else {
    form.post(`/reservations/${selected.value.id}`, options)
  }
}
</script>
