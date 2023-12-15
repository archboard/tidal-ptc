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
          <DynamicFormFields
            v-model="inertiaForm"
            :errors="inertiaForm.errors"
            :fields="fields"
          />
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
import DynamicFormFields from '@/components/forms/fields/DynamicFormFields.vue'
import clone from 'just-clone'
import Installation from '@/layouts/Installation.vue'

const props = defineProps({
  form: Object,
  email: String,
  fields: Array,
})
const inertiaForm = useForm({
  ...clone(props.form),
  email: props.email,
})
</script>
