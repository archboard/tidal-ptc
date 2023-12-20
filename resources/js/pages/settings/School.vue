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

      <SchoolTimeSlotSettings :school="school" />
      <SchoolTranslatorSettings :school="school" />
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
import SchoolTimeSlotSettings from '@/components/forms/form-sets/SchoolTimeSlotSettings.vue'
import SchoolTranslatorSettings from '@/components/forms/form-sets/SchoolTranslatorSettings.vue'

const props = defineProps({
  school: Object,
  counts: Object,
})
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
    console.error(err)
    uiState.value = null
  }
}
</script>
