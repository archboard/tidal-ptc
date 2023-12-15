<template>
  <Authenticated>
    <div class="space-y-8">
      <AppForm :form="tenantForm" />

      <AppForm
        v-if="smtpForm"
        :form="smtpForm"
      >
        <template #actions="{ loading }">
          <AppButton @click.prevent="sendSmtpTest" type="button" color="white" :loading="uiState === 'sending'">{{ __('Send test') }}</AppButton>
          <AppButton type="submit" :loading="loading" />
        </template>
      </AppForm>

      <form @submit.prevent="saveSchools">
        <CardWrapper>
          <CardPadding>
            <CardHeader>{{ __('Active schools') }}</CardHeader>
            <HelpText>{{ __('Select the schools that are active in the app.') }}</HelpText>
          </CardPadding>
          <CardPadding>
            <SimpleAlert v-if="!editable" level="warning" class="mb-4">
              {{ __('Schools are managed in your Archboard account.') }}
            </SimpleAlert>
            <SimpleAlert v-if="schoolForm.errors.schools" level="error" class="mb-4">
              {{ schoolForm.errors.schools }}
            </SimpleAlert>

            <div class="flex items-center space-x-2 mb-2 text-sm">
              <AppLink is="button" @click.prevent="select(true)">{{ __('Select all') }}</AppLink>
              <AppLink is="button" @click.prevent="select(false)">{{ __('Select none') }}</AppLink>
            </div>

            <template v-for="school in schools" :key="school.id">
              <div>
                <AppCheckbox v-model="schoolForm.schools" :value="school.id" :disabled="!editable">
                  {{ school.name }}
                </AppCheckbox>
              </div>
            </template>
          </CardPadding>
          <CardAction>
            <AppButton type="submit" :loading="schoolForm.processing" :disabled="!editable">
              {{ __('Save') }}
            </AppButton>
          </CardAction>
        </CardWrapper>
      </form>
    </div>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import { router, useForm } from '@inertiajs/vue3'
import CardAction from '@/components/CardAction.vue'
import AppForm from '@/components/forms/AppForm.vue'
import AppButton from '@/components/AppButton.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppLink from '@/components/AppLink.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import { ref } from 'vue'

const props = defineProps({
  smtpForm: Object,
  tenantForm: Object,
  schools: Array,
  editable: Boolean,
})
const uiState = ref()
const schoolForm = useForm({
  schools: props.schools.reduce((carry, school) => {
    if (school.active) {
      carry.push(school.id)
    }

    return carry
  }, []),
})
const select = all => {
  if (props.editable) {
    schoolForm.schools = all
      ? props.schools.map(school => school.id)
      : []
  }
}
const saveSchools = () => {
  if (props.editable) {
    schoolForm.put(`/settings/tenant/schools`, {
      preserveScroll: true,
    })
  }
}
const sendSmtpTest = () => {
  uiState.value = 'sending'

  router.post('/settings/tenant/smtp/test', null, {
    preserveScroll: true,
    onSuccess: () => {
      uiState.value = null
    }
  })
}
</script>
