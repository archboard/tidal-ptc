<template>
  <form @submit.prevent="submit" data-cy="personal-details">
    <SplitForm cancel="/settings/personal" :loading="form.processing">
      <template #headline>
        <Headline3>{{ __('Personal details') }}</Headline3>
        <HelpText>{{ __(`These items are synchronized from your SIS, so they may be overwritten on the next sync or log in.`) }}</HelpText>
      </template>

      <FormField v-model="form.first_name" :error="form.errors.first_name" class="col-span-6 sm:col-span-3">
        {{ __('First name') }}
      </FormField>
      <FormField v-model="form.last_name" :error="form.errors.last_name" class="col-span-6 sm:col-span-3">
        {{ __('Last name') }}
      </FormField>
      <FormField v-model="form.email" :error="form.errors.email" type="email" class="col-span-6 sm:col-span-3">
        {{ __('Email') }}
      </FormField>
      <FormField :error="form.errors.timezone" class="col-span-6 sm:col-span-3">
        {{ __('Timezone') }}
        <template #component="{ id, hasError }">
          <TimezoneCombobox v-model="form.timezone" :id="id" :has-error="hasError" />
        </template>
      </FormField>

      <template #actions="{ loading }">
        <AppButton type="button" @click.prevent="sync" :loading="syncing" color="white">{{ __('Sync from :sis', { sis: tenant.sis }) }}</AppButton>
        <AppButton type="submit" :loading="loading">{{ __('Save') }}</AppButton>
      </template>
    </SplitForm>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import SplitForm from '@/components/SplitForm.vue'
import Headline3 from '@/components/Headline3.vue'
import HelpText from '@/components/forms/HelpText.vue'
import FormField from '@/components/forms/FormField.vue'
import useProp from '@/composition/useProp.js'
import TimezoneCombobox from '@/components/forms/TimezoneCombobox.vue'
import AppButton from '@/components/AppButton.vue'
import { watch } from 'vue'
import useSisObjectSync from '@/composition/useSisObjectSync.js'

const user = useProp('user')
const tenant = useProp('tenant')
const form = useForm({
  first_name: user.value.first_name,
  last_name: user.value.last_name,
  email: user.value.email,
  timezone: user.value.timezone,
})
const { syncing, sync } = useSisObjectSync('user', user)
const submit = () => {
  form.put('/settings/personal', {
    preserveScroll: true,
  })
}

watch(user, value => {
  form.first_name = value.first_name
  form.last_name = value.last_name
  form.email = value.email
  form.timezone = value.timezone
})
</script>
