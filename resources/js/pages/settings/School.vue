<template>
  <Authenticated>
    <template #actions>
      <ActionWrapper>
        <AppButton size="sm" :loading="syncing || props.syncing.length > 0" @click.prevent="sync()">
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
          :loading="uiState === item.key || isSyncing(item.key)"
          :action-text="__('Sync')"
          :icon="UsersIcon"
          @action="syncItem(item.key)"
        >
          {{ item.label }}
          <template v-if="item.key === 'sections' && enrollmentSync" #footer>
            {{ __('Enrollment') }} {{ enrollmentSync.processed }}/{{ enrollmentSync.total }}
          </template>
        </ActionStat>
      </dl>

      <SchoolTimeSlotSettings :school="school" />
      <SchoolTranslatorSettings :school="school" />
    </Spacer>
  </Authenticated>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import AppButton from '@/components/AppButton.vue'
import ActionWrapper from '@/components/ActionWrapper.vue'
import useSisObjectSync from '@/composition/useSisObjectSync.js'
import ActionStat from '@/components/ActionStat.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'
import { router, useForm, usePoll } from '@inertiajs/vue3'
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
import SchoolTimeSlotSettings from '@/components/forms/form-sets/SchoolTimeSlotSettings.vue'
import SchoolTranslatorSettings from '@/components/forms/form-sets/SchoolTranslatorSettings.vue'

const props = defineProps({
  school: Object,
  counts: Object,
  enrollmentSync: Object,
  syncing: { type: Array, default: () => [] },
})
const isSyncing = item => props.syncing.includes(item)
// Poll sync state (and the counts it changes) only while something is running.
// Refresh flash too, or the stale 'Sync started' toast repeats every poll.
const poll = usePoll(3000, { only: ['enrollmentSync', 'counts', 'syncing', 'flash'] }, { autoStart: false })
watch(() => props.enrollmentSync || props.syncing.length > 0, active => active ? poll.start() : poll.stop(), { immediate: true })
const { syncing, sync } = useSisObjectSync('school', props.school)
const { dayjs } = useDates()
const uiState = ref()
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
    uiState.value = null
  }
}
</script>
