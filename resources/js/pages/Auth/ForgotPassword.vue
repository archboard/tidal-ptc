<template>
  <Layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
      {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <form @submit.prevent="submit">
      <FormField :error="form.errors.user_type" class="mb-4">
        {{ __('Account type') }}
        <template #component>
          <RadioGroup v-model="form.user_type" :options="userTypes" />
        </template>
      </FormField>

      <FormField v-model="form.email" :error="form.errors.email" type="email">
        {{ __('Email') }}
      </FormField>

      <div class="mt-6">
        <Button :loading="form.processing" full>
          {{ __('Email Password Reset Link') }}
        </Button>
      </div>
    </form>
  </Layout>
</template>

<script setup>
import Button from '@/components/AppButton.vue'
import Layout from '@/layouts/Guest.vue'
import FormField from '@/components/forms/FormField.vue'
import RadioGroup from '@/components/forms/RadioGroup.vue'
import { UserType } from '@/Enums/UserType.enum.js'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  status: String,
  userTypes: Array,
})
const form = useForm({
  user_type: UserType.staff,
  email: '',
})
const submit = () => {
  form.post('/forgot-password')
}
</script>
