<template>
  <form @submit.prevent="save">
    <CardWrapper>
      <CardPadding v-if="form.title">
        <CardHeader>{{ form.title }}</CardHeader>
      </CardPadding>
      <CardPadding>
        <DynamicFormFields
          v-model="inertiaForm"
          :fields="form.fields"
          :errors="inertiaForm.errors"
        />
      </CardPadding>
      <CardAction>
        <slot name="actions" :loading="inertiaForm.processing">
          <AppButton type="submit" :loading="inertiaForm.processing" />
        </slot>
      </CardAction>
    </CardWrapper>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import { useForm } from '@inertiajs/vue3'
import DynamicFormFields from '@/components/forms/fields/DynamicFormFields.vue'
import CardAction from '@/components/CardAction.vue'
import AppButton from '@/components/AppButton.vue'
import clone from 'just-clone'

const props = defineProps({
  form: Object,
})
const emit = defineEmits(['saved'])
const inertiaForm = useForm(clone(props.form.values))
const save = () => {
  inertiaForm.post(props.form.endpoint, {
    preserveScroll: true,
    onSuccess: () => {
      emit('saved')
    },
  })
}
</script>
