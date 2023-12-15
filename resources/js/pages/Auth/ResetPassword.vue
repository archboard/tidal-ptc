<template>
  <Layout>
    <form @submit.prevent="submit">
      <Fieldset>
        <FormField v-model="form.email" :error="form.errors.email" type="email">
          {{ __('Email') }}
        </FormField>

        <FormField v-model="form.password" :error="form.errors.password" type="password">
          {{ __('Password') }}
        </FormField>

        <FormField v-model="form.password_confirmation" :error="form.errors.password_confirmation" type="password">
          {{ __('Confirm password') }}
        </FormField>

        <Button :loading="form.processing" full>
          {{ __('Reset Password') }}
        </Button>
      </Fieldset>
    </form>
  </Layout>
</template>

<script setup>
import Fieldset from '@/components/forms/AppFieldset.vue'
import Button from '@/components/AppButton.vue'
import Layout from '@/layouts/Guest.vue'
import { useForm } from '@inertiajs/vue3'
import FormField from '@/components/forms/FormField.vue'

const props = defineProps({
  email: String,
  token: String,
})
const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})
const submit = () => {
  form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
