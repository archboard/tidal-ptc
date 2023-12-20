<template>
  <form @submit.prevent="save">
    <SplitForm :loading="form.processing">
      <template #headline>
        <Headline3>{{ __('Notification preferences') }}</Headline3>
        <HelpText>{{ __('Manage when you receive email notifications.') }}</HelpText>
      </template>

      <template v-for="notification in notificationOptions" :key="notification.key">
        <FormField :error="form.errors[notification.key]" :help="notification.description" class="col-span-6">
          <template #component>
            <AppCheckbox v-model="form[notification.key]">
              {{ notification.label }}
            </AppCheckbox>
          </template>
        </FormField>
      </template>
    </SplitForm>
  </form>
</template>

<script setup>
import SplitForm from '@/components/SplitForm.vue'
import { useForm } from '@inertiajs/vue3'
import Headline3 from '@/components/Headline3.vue'
import HelpText from '@/components/forms/HelpText.vue'
import FormField from '@/components/forms/FormField.vue'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'

const props = defineProps({
  notificationOptions: Array,
  userNotifications: [Object, Array],
})
const emit = defineEmits([])
const form = useForm(props.notificationOptions.reduce((carry, notification) => {
  carry[notification.key] = props.userNotifications[notification.key] ?? false
  return carry
}, {}))
const save = () => {
  form.put('/settings/personal/notifications', {
    preserveScroll: true,
  })
}
</script>
