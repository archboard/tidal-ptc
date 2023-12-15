<template>
  <Installation>
    <form @submit.prevent="inertiaForm.post(endpoint)">
      <CardWrapper>
        <CardPadding>
          <CardHeader>
            {{ __('Add your user account') }}
          </CardHeader>
        </CardPadding>
        <CardPadding>
          <AppFieldset>
            <p>{{ __('The user you select will be given full permissions to your tenancy/district and you will be logged in.') }}</p>
            <FormField :error="inertiaForm.errors['user.sis_id']">
              {{ __('Import SIS User') }}
              <template #component="{ id, hasError }">
                <AppCombobox
                  v-model="inertiaForm.user"
                  v-model:query="query"
                  :options="options"
                  :id="id"
                  :has-error="hasError"
                  option-key="sis_key"
                  :placeholder="__('Search by name or email…')"
                  :loading="loading"
                  :display-value="displayItem"
                />
              </template>
            </FormField>
          </AppFieldset>
        </CardPadding>
        <CardAction>
          <AppButton type="submit" :loading="inertiaForm.processing" full>
            {{ __('Add user and log in') }}
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
import Installation from '@/layouts/Installation.vue'
import FormField from '@/components/forms/FormField.vue'
import { watchDebounced } from '@vueuse/core'
import { inject, ref } from 'vue'
import AppCombobox from '@/components/forms/AppCombobox.vue'
import AppFieldset from '@/components/forms/AppFieldset.vue'

const props = defineProps({
  endpoint: String,
})
const inertiaForm = useForm({
  user: {},
})
const options = ref([])
const query = ref()
const loading = ref(false)
const $http = inject('$http')
const displayItem = (item) => {
  if (!item.sis_id) {
    return ''
  }
  let prefix = ''

  if (item.name && item.email) {
    prefix = `${item.name} (${item.email})`
  }

  if (!item.name || !item.email) {
    prefix = item.name || item.email
  }

  return `${prefix} SIS ID: ${item.sis_id}`
}

watchDebounced(query, async (value) => {
  options.value = []

  if (!query.value) {
    return
  }

  loading.value = true

  try {
    const { data } = await $http.post('/search/sis/user', {
      search: value
    })

    options.value = data
  } catch (err) {}

  loading.value = false
}, { debounce: 500 })
</script>
