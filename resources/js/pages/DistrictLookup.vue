<template>
  <Layout>
    <form @submit.prevent="form.post('/district')">
      <AppFieldset>
        <p class="text-sm text-gray-600 dark:text-gray-300">
          {{ __('Enter the four character code your district gave you to continue to its login page.') }}
        </p>

        <FormField :error="form.errors.code">
          {{ __('District code') }}
          <template #component="{ hasError, id }">
            <AppInput v-model="form.code" :id="id" class="text-xl uppercase tracking-widest" maxlength="4" autocomplete="off" autofocus :has-error="hasError" />
          </template>
        </FormField>

        <AppButton :loading="form.processing" full>
          {{ __('Continue') }}
        </AppButton>
      </AppFieldset>
    </form>
  </Layout>
</template>

<script setup>
import Layout from '@/layouts/Guest.vue'
import { useForm } from '@inertiajs/vue3'
import AppFieldset from '@/components/forms/AppFieldset.vue'
import FormField from '@/components/forms/FormField.vue'
import AppButton from '@/components/AppButton.vue'
import AppInput from '@/components/forms/AppInput.vue'

const form = useForm({
  code: '',
})
</script>
