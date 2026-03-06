<template>
  <Authenticated>
    <div class="space-y-8">
      <form @submit.prevent="tenantForm.put('/settings/tenant', { preserveScroll: true })">
        <CardWrapper>
          <CardPadding>
            <CardHeader>{{ __('Tenant Settings') }}</CardHeader>
          </CardPadding>
          <CardPadding>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
              <div class="md:col-span-6">
                <InputField
                  v-model="tenantForm.name"
                  :error="tenantForm.errors.name"
                  :label="__('Tenant name')"
                  required
                />
              </div>
              <div class="md:col-span-3">
                <InputField
                  v-model="tenantForm.domain"
                  :error="tenantForm.errors.domain"
                  :label="__('Domain')"
                  :disabled="isCloud"
                  required
                />
              </div>
              <div class="md:col-span-3">
                <SelectField
                  v-model="tenantForm.sis_provider"
                  :error="tenantForm.errors.sis_provider"
                  :label="__('SIS data provider')"
                  :options="sisOptions"
                  required
                />
              </div>
              <div class="md:col-span-6">
                <CheckboxField
                  v-model="tenantForm.allow_password_auth"
                  :error="tenantForm.errors.allow_password_auth"
                  :label="__('Allow password authentication')"
                  :help-text="__('Allow users to login with their email and password.')"
                />
              </div>
              <div class="md:col-span-6">
                <CheckboxField
                  v-model="tenantForm.allow_oidc_login"
                  :error="tenantForm.errors.allow_oidc_login"
                  :label="__('Allow OpenID Connect login')"
                  :help-text="__('Allow users to login with OpenID Connect with the SIS.')"
                />
              </div>
            </div>
          </CardPadding>
          <CardAction>
            <AppButton type="submit" :loading="tenantForm.processing" />
          </CardAction>
        </CardWrapper>
      </form>

      <form v-if="smtp" @submit.prevent="smtpForm.put('/settings/tenant/smtp', { preserveScroll: true })">
        <CardWrapper>
          <CardPadding>
            <CardHeader>{{ __('SMTP Settings') }}</CardHeader>
          </CardPadding>
          <CardPadding>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
              <div class="md:col-span-3">
                <InputField
                  v-model="smtpForm.host"
                  :error="smtpForm.errors.host"
                  :label="__('Host')"
                  placeholder="127.0.0.1"
                  :help-text="__('IP address or domain name of the SMTP server.')"
                  required
                />
              </div>
              <div class="md:col-span-3">
                <NumberField
                  v-model="smtpForm.port"
                  :error="smtpForm.errors.port"
                  :label="__('Port')"
                  placeholder="587"
                  required
                />
              </div>
              <div class="md:col-span-3">
                <InputField
                  v-model="smtpForm.username"
                  :error="smtpForm.errors.username"
                  :label="__('Username')"
                />
              </div>
              <div class="md:col-span-3">
                <PasswordField
                  v-model="smtpForm.password"
                  :error="smtpForm.errors.password"
                  :label="__('Password')"
                />
              </div>
              <div class="md:col-span-2">
                <InputField
                  v-model="smtpForm.from_name"
                  :error="smtpForm.errors.from_name"
                  :label="__('From name')"
                  placeholder="App Name"
                  required
                />
              </div>
              <div class="md:col-span-2">
                <EmailField
                  v-model="smtpForm.from_address"
                  :error="smtpForm.errors.from_address"
                  :label="__('From address')"
                  required
                />
              </div>
              <div class="md:col-span-2">
                <SelectField
                  v-model="smtpForm.encryption"
                  :error="smtpForm.errors.encryption"
                  :label="__('Encryption')"
                  :options="{ tls: 'TLS', ssl: 'SSL' }"
                />
              </div>
            </div>
          </CardPadding>
          <CardAction>
            <AppButton @click.prevent="sendSmtpTest" type="button" color="white" :loading="uiState === 'sending'">{{ __('Send test') }}</AppButton>
            <AppButton type="submit" :loading="smtpForm.processing" />
          </CardAction>
        </CardWrapper>
      </form>

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
import AppButton from '@/components/AppButton.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import AppLink from '@/components/AppLink.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import InputField from '@/components/forms/fields/InputField.vue'
import SelectField from '@/components/forms/fields/SelectField.vue'
import CheckboxField from '@/components/forms/fields/CheckboxField.vue'
import NumberField from '@/components/forms/fields/NumberField.vue'
import PasswordField from '@/components/forms/fields/PasswordField.vue'
import EmailField from '@/components/forms/fields/EmailField.vue'
import { ref } from 'vue'

const props = defineProps({
  tenant: Object,
  isCloud: Boolean,
  smtp: Object,
  sisOptions: Array,
  schools: Array,
  editable: Boolean,
})

const uiState = ref()

const tenantForm = useForm({
  name: props.tenant.name,
  domain: props.tenant.domain,
  sis_provider: props.tenant.sis_provider,
  allow_password_auth: props.tenant.allow_password_auth,
  allow_oidc_login: props.tenant.allow_oidc_login,
})

const smtpForm = useForm({
  host: props.smtp?.host ?? null,
  port: props.smtp?.port ?? null,
  username: props.smtp?.username ?? null,
  password: props.smtp?.password ?? null,
  from_name: props.smtp?.from_name ?? null,
  from_address: props.smtp?.from_address ?? null,
  encryption: props.smtp?.encryption ?? null,
})

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
