<template>
  <Authenticated>
    <form @submit.prevent="save">
      <div class="lg:w-1/2">
        <CardWrapper>
          <CardPadding>
            <AppFieldset>
              <FormField :error="form.errors.alt_user_id">
                {{ __('Override teacher') }}
                <template #component="{ hasError, id }">
                  <ModelCombobox
                    v-model="form.alt_user_id"
                    model="user"
                    :id="id"
                    :has-error="hasError"
                  />
                </template>
              </FormField>

              <FormField :error="form.errors.can_book">
                <template #component>
                  <AppCheckbox v-model="form.can_book">
                    {{ __('Section accepts bookings') }}
                  </AppCheckbox>
                </template>
              </FormField>
            </AppFieldset>
          </CardPadding>
          <CardAction :loading="form.processing" />
        </CardWrapper>
      </div>
    </form>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import { useForm } from '@inertiajs/vue3'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import AppFieldset from '@/components/forms/AppFieldset.vue'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import CardAction from '@/components/CardAction.vue'
import ModelCombobox from '@/components/forms/ModelCombobox.vue'

const props = defineProps({
  section: Object,
  endpoint: String,
})
const form = useForm({
  alt_user_id: props.section.alt_user_id,
  can_book: props.section.can_book,
})
const save = () => {
  form.put(props.endpoint)
}
</script>
