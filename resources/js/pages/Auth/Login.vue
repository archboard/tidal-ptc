<template>
  <Layout>
    <div>
      <span v-if="tenant.allow_oidc_login" class="rounded-md shadow-sm self-start flex w-full">
        <a :href="`/auth/powerschool/oidc/authenticate?remember=${form.remember ? '1' : '0'}`" class="px-4 py-3 text-base leading-6 flex w-full border border-transparent font-medium rounded-md focus:outline-none ease-in-out duration-150 items-center justify-center text-white bg-[#00427c] hover:bg-[#003463] focus:ring focus:ring-[#006bc9] focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
          <svg class="w-4 mr-2" viewBox="0 0 20 26" xmlns="http://www.w3.org/2000/svg"><path d="M6.153 0c-.3 0-.542.237-.542.528v24.287H3.886V.528A.535.535 0 003.344 0H.542C.242 0 0 .237 0 .528v24.815c0 .291.243.528.542.528.3 0 .542-.237.542-.528V1.056h1.718v24.287c0 .291.243.528.542.528h2.81c.299 0 .542-.237.542-.528V1.056h2.068c5.75 0 9.768 3.41 9.768 8.292 0 4.7-3.766 8.028-9.225 8.234v-1.587c4.222-.216 7.231-2.953 7.231-6.667 0-3.934-3.196-6.681-7.773-6.681a.55.55 0 00-.384.155.52.52 0 00-.159.373l.003 2.756c0 .291.243.527.543.527 1.717 0 3.45.888 3.45 2.87 0 1.69-1.419 2.87-3.45 2.87-.3 0-.543.236-.543.528 0 .291.243.528.543.528 2.627 0 4.535-1.652 4.535-3.926 0-2.152-1.616-3.7-3.994-3.903l-.001-1.707c3.646.203 6.146 2.453 6.146 5.61 0 3.312-2.75 5.625-6.69 5.625-.299 0-.542.236-.542.528v2.64c0 .29.243.527.543.527 6.288 0 10.851-3.911 10.851-9.3C19.616 3.844 15.154 0 8.765 0H6.153z" fill="currentColor" fill-rule="evenodd"/></svg>
          {{ __('Sign in with PowerSchool') }}
        </a>
      </span>

      <BorderSeparator v-if="tenant.allow_oidc_login && tenant.allow_password_auth" class="my-6">
        {{ __('Or continue with') }}
      </BorderSeparator>
    </div>

    <form v-if="tenant.allow_password_auth" @submit.prevent="submit">
      <AppFieldset>
        <FormField :error="form.errors.email">
          {{ __('Email') }}
          <template #component="{ hasError }">
            <AppInput v-model="form.email" class="text-xl" type="email" :has-error="hasError" />
          </template>
        </FormField>

        <FormField :error="form.errors.password">
          {{ __('Password') }}
          <template #component="{ hasError }">
            <AppInput v-model="form.password" class="text-xl" type="password" :has-error="hasError" />
          </template>
        </FormField>

        <FormField>
          <template #component>
            <AppCheckbox v-model="form.remember">
              {{ __('Keep me logged in') }}
            </AppCheckbox>
          </template>
        </FormField>

        <AppButton :loading="form.processing" full>
          {{ __('Log in') }}
        </AppButton>

        <p>
          <AppLink v-if="tenant.allow_password_auth" href="/forgot-password">
            {{ __('Forgot your password?') }}
          </AppLink>
        </p>
      </AppFieldset>
    </form>

    <p v-if="!tenant.allow_password_auth && !tenant.allow_oidc_login">
      No authentication methods are available.
    </p>
  </Layout>
</template>

<script setup>
import Layout from '@/layouts/Guest.vue'
import { useForm } from '@inertiajs/vue3'
import AppFieldset from '@/components/forms/AppFieldset.vue'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppButton from '@/components/AppButton.vue'
import BorderSeparator from '@/components/BorderSeparator.vue'
import AppLink from '@/components/AppLink.vue'
import AppInput from '@/components/forms/AppInput.vue'

const props = defineProps({
  status: String,
  tenant: Object,
})
const form = useForm({
  email: '',
  password: '',
  remember: false
})
const submit = () => {
  form.post('/login', {
    onError: () => {
      form.reset('password')
    }
  })
}
</script>
