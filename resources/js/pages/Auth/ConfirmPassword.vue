<template>
  <div class="mb-4 text-sm text-gray-600">
    {{ __('This is a secure area of the application.Please confirm your password before continuing.') }}
  </div>

  <form @submit.prevent="submit">
    <FormField v-model="form.password" :error="form.errors.password" type="password">
      {{ __('Password') }}
    </FormField>

    <div class="flex justify-end mt-4">
      <Button :loading="form.processing">
        {{ __('Confirm') }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import Button from '@/components/AppButton.vue'
import FormField from '@/components/forms/FormField.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  password: '',
})
const submit = () => {
  form.post('user/confirm-password', {
    onFinish: () => form.reset(),
  })
}
</script>
