<template>
  <Installation>
    <form @submit.prevent="inertiaForm.post('/install')">
      <CardWrapper>
        <CardPadding>
          <CardHeader>
            {{ __('Installation') }}
          </CardHeader>
        </CardPadding>
        <CardPadding>
          <SimpleAlert v-if="isPowerSchool" level="info" class="mb-5">
            {{ __('Install the PowerSchool plugin first to get a client ID and secret.') }}
            <AppLink is="a" href="/install/plugin">{{ __('Download plugin') }}</AppLink>
          </SimpleAlert>
          <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.name"
                :error="inertiaForm.errors.name"
                :label="__('Tenant name')"
                required
              />
            </div>
            <div v-if="!isCloud" class="md:col-span-6">
              <InputField
                v-model="inertiaForm.domain"
                :error="inertiaForm.errors.domain"
                :label="__('Domain')"
                required
              />
            </div>
            <div class="md:col-span-6">
              <SelectField
                v-model="inertiaForm.sis_provider"
                :error="inertiaForm.errors.sis_provider"
                :label="__('SIS data provider')"
                :options="sisOptions"
                required
              />
            </div>
            <template v-if="isPowerSchool">
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.sis_config.url"
                :error="inertiaForm.errors['sis_config.url']"
                :label="__('PowerSchool URL')"
                type="url"
                required
              />
            </div>
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.sis_config.client_id"
                :error="inertiaForm.errors['sis_config.client_id']"
                :label="__('PowerSchool Client ID')"
                required
              />
            </div>
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.sis_config.client_secret"
                :error="inertiaForm.errors['sis_config.client_secret']"
                :label="__('PowerSchool Client Secret')"
                required
              />
            </div>
            </template>
            <template v-else>
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.sis_config.url"
                :error="inertiaForm.errors['sis_config.url']"
                :label="__('OneRoster base URL')"
                type="url"
                required
              />
            </div>
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.sis_config.token_url"
                :error="inertiaForm.errors['sis_config.token_url']"
                :label="__('Token URL')"
                type="url"
                required
              />
            </div>
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.sis_config.client_id"
                :error="inertiaForm.errors['sis_config.client_id']"
                :label="__('ClassLink Client ID')"
                required
              />
            </div>
            <div class="md:col-span-6">
              <PasswordField
                v-model="inertiaForm.sis_config.client_secret"
                :error="inertiaForm.errors['sis_config.client_secret']"
                :label="__('ClassLink Client Secret')"
                required
              />
            </div>
            </template>
            <div v-if="!isCloud" class="md:col-span-6">
              <CheckboxField
                v-model="inertiaForm.enable_classlink"
                :label="__('Enable ClassLink sign-in')"
                :help-text="__('Optional. Registers this site with the sign-in broker using your license key.')"
              />
            </div>
            <div v-if="inertiaForm.enable_classlink" class="md:col-span-6">
              <InputField
                v-model="inertiaForm.license"
                :error="inertiaForm.errors.license"
                :label="__('License key')"
                required
              />
            </div>
          </div>
        </CardPadding>
        <CardAction>
          <AppButton type="submit" :loading="inertiaForm.processing" full>
            {{ __('Install') }}
          </AppButton>
        </CardAction>
      </CardWrapper>
    </form>
  </Installation>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import AppButton from '@/components/AppButton.vue'
import CardAction from '@/components/CardAction.vue'
import CheckboxField from '@/components/forms/fields/CheckboxField.vue'
import SelectField from '@/components/forms/fields/SelectField.vue'
import PasswordField from '@/components/forms/fields/PasswordField.vue'
import InputField from '@/components/forms/fields/InputField.vue'
import Installation from '@/layouts/Installation.vue'
import SimpleAlert from '@/components/alerts/SimpleAlert.vue'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  name: String,
  domain: String,
  sisConfig: Object,
  sisProvider: { type: String, default: 'ps' },
  sisOptions: Array,
  isCloud: Boolean,
  channel: String,
})

const inertiaForm = useForm({
  name: props.name ?? '',
  enable_classlink: false,
  license: '',
  domain: props.domain ?? '',
  sis_provider: props.sisProvider,
  sis_config: {
    url: props.sisConfig?.url ?? '',
    token_url: props.sisConfig?.token_url ?? '',
    client_id: props.sisConfig?.client_id ?? '',
    client_secret: props.sisConfig?.client_secret ?? '',
  },
})

const isPowerSchool = computed(() => inertiaForm.sis_provider === 'ps')

// PowerSchool registers the plugin as it's enabled, which fills in its connection details
onMounted(() => {
  window.Echo?.channel(props.channel)
    .listen('PowerSchoolPluginRegistered', ({ sisConfig }) => Object.assign(inertiaForm.sis_config, sisConfig))
})
onUnmounted(() => window.Echo?.leave(props.channel))
</script>
