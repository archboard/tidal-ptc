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
          <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.name"
                :error="inertiaForm.errors.name"
                :label="__('Tenant name')"
                required
              />
            </div>
            <div class="md:col-span-6">
              <InputField
                v-model="inertiaForm.domain"
                :error="inertiaForm.errors.domain"
                :label="__('Domain')"
                required
              />
            </div>
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
import { useForm } from '@inertiajs/vue3'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import AppButton from '@/components/AppButton.vue'
import CardAction from '@/components/CardAction.vue'
import InputField from '@/components/forms/fields/InputField.vue'
import Installation from '@/layouts/Installation.vue'

const props = defineProps({
  name: String,
  domain: String,
  sisConfig: Object,
})

const inertiaForm = useForm({
  name: props.name ?? '',
  domain: props.domain ?? '',
  sis_config: {
    url: props.sisConfig?.url ?? '',
    client_id: props.sisConfig?.client_id ?? '',
    client_secret: props.sisConfig?.client_secret ?? '',
  },
})
</script>
