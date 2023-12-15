<template>
  <form @submit.prevent="submit" data-cy="security-settings">
    <SplitForm cancel="/settings/personal" :loading="form.processing">
      <template #headline>
        <Headline3>{{ __('Security settings') }}</Headline3>
        <HelpText>{{ __(`Change your password.`) }}</HelpText>
      </template>

      <FormField v-if="hasPassword" v-model="form.current_password" :error="form.errors.current_password" type="password" class="col-span-6 sm:col-span-3">
        {{ __('Current password') }}
      </FormField>
      <FormField v-model="form.password" :error="form.errors.password" type="password" class="col-span-6 sm:col-span-3 sm:col-start-1">
        {{ __('New password') }}
      </FormField>
      <FormField v-model="form.password_confirmation" :error="form.errors.password_confirmation" type="password" class="col-span-6 sm:col-span-3">
        {{ __('Confirm new password') }}
      </FormField>
    </SplitForm>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import SplitForm from '@/components/SplitForm.vue'
import Headline3 from '@/components/Headline3.vue'
import HelpText from '@/components/forms/HelpText.vue'
import FormField from '@/components/forms/FormField.vue'

const props = defineProps({
  hasPassword: Boolean,
})
const form = useForm({
  current_password: null,
  password: null,
  password_confirmation: null,
})
const submit = () => {
  form.put('/user/password', {
    preserveScroll: true,
    onSuccess () {
      form.reset()
    }
  })
}
</script>
