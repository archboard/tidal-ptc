<template>
  <div>
    <HelpText v-if="existingReservation">
      {{ __('Currently booked for :when. Pick a new time below to move it.', { when: displayDate(existingReservation.starts_at, 'full') }) }}
    </HelpText>
    <HelpText v-else>{{ __('Choose a time that works for you.') }}</HelpText>

    <p v-if="selectableSlots.length === 0" class="mt-4">{{ __('There are no available times right now.') }}</p>

    <div v-if="slots.length" class="mt-4 flex justify-end">
      <ButtonGroup v-model="view" :options="viewOptions" />
    </div>

    <TimeSlotCalendar
      v-if="view === 'calendar'"
      ref="calendarRef"
      class="mt-4"
      :time-format="user.fc_time_format"
      :timezone="timezone"
      :events="events"
      :options-override="{ selectable: false, editable: false, initialDate: events[0]?.start, height: 700 }"
      @event-click="selectEvent"
    />

    <div v-for="(daySlots, day) in slotsByDay" v-else :key="day" class="mt-4">
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
          <span v-if="isCurrentSlot(slot)" class="ml-1">· {{ __('Current') }}</span>
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
import { computed, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { trans as __ } from 'laravel-vue-i18n'
import HelpText from '@/components/forms/HelpText.vue'
import FormField from '@/components/forms/FormField.vue'
import FieldError from '@/components/forms/FieldError.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppSelect from '@/components/forms/AppSelect.vue'
import AppButton from '@/components/AppButton.vue'
import ButtonGroup from '@/components/ButtonGroup.vue'
import { ListBulletIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline/index.js'
import TimeSlotCalendar from '@/components/TimeSlotCalendar.vue'
import useDates from '@/composition/useDates.js'

const props = defineProps({
  student: Object,
  slots: Array,
  existingReservation: Object,
  conflicts: { type: Array, default: () => [] },
  languages: Array,
  allowOnline: Boolean,
})
const emit = defineEmits(['success'])
const { displayDate, timezone } = useDates()
const user = usePage().props.user
const view = ref('list')
const viewOptions = [
  { value: 'list', label: __('List'), icon: ListBulletIcon },
  { value: 'calendar', label: __('Calendar'), icon: CalendarDaysIcon },
]
const calendarRef = ref()
const selected = ref(props.existingReservation ?? null)
const isCurrent = computed(() => selected.value?.id === props.existingReservation?.id)
const form = useForm({
  student_id: props.student.id,
  contact_notes: props.existingReservation?.contact_notes ?? '',
  requested_online: props.existingReservation?.requested_online ?? false,
  language: props.existingReservation?.language ?? null,
})
const isCurrentSlot = slot => slot.id === props.existingReservation?.id
const hasConflict = slot => props.conflicts.some(other => other.starts_at < slot.ends_at && other.ends_at > slot.starts_at)
const isSelectable = slot => slot && (isCurrentSlot(slot) || (!slot.student_id && !hasConflict(slot)))
// Event styling: available = primary, selected = darker primary, booked by someone else = gray, mine = dark/light gray, conflicting = gray
const eventClasses = slot => {
  if (selected.value?.id === slot.id) return ['bg-primary-800!', 'border-primary-800!']
  if (isCurrentSlot(slot)) return ['bg-gray-700!', 'border-gray-700!', 'dark:bg-gray-200!', 'dark:border-gray-200!', 'dark:text-gray-900!']
  if (slot.student_id || hasConflict(slot)) return ['bg-gray-400!', 'border-gray-400!', 'cursor-not-allowed!']
  return []
}
const eventTitle = slot => {
  if (isCurrentSlot(slot)) return __('Current')
  if (slot.student_id) return __('Booked')
  if (hasConflict(slot)) return __('Conflict')
  return ''
}
const selectableSlots = computed(() => props.slots.filter(isSelectable))
const events = computed(() => props.slots.map(slot => ({
  id: slot.id,
  title: eventTitle(slot),
  start: `${slot.starts_at.replace(' ', 'T')}Z`,
  end: `${slot.ends_at.replace(' ', 'T')}Z`,
  classNames: eventClasses(slot),
})))
// ponytail: FullCalendar copies its options once, so restyle events through the API instead of re-rendering
watch(selected, () => {
  const api = calendarRef.value?.calendar?.getApi?.()
  props.slots.forEach(slot => api?.getEventById(String(slot.id))?.setProp('classNames', eventClasses(slot)))
})
const selectEvent = ({ event }) => {
  const slot = props.slots.find(slot => slot.id === Number(event.id))
  if (isSelectable(slot)) selected.value = slot
}
const slotsByDay = computed(() => selectableSlots.value
  .reduce((carry, slot) => {
    const day = displayDate(slot.starts_at, 'dddd, MMMM D')
    ;(carry[day] ??= []).push(slot)
    return carry
  }, {}))
const submit = () => {
  const options = {
    onSuccess: () => emit('success'),
    preserveScroll: true,
  }
  if (props.existingReservation) {
    form.transform(data => ({ ...data, time_slot_id: selected.value.id }))
      .put(`/reservations/${props.existingReservation.id}`, options)
  } else {
    form.post(`/reservations/${selected.value.id}`, options)
  }
}
</script>
