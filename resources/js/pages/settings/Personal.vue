<template>
  <Authenticated>
    <Spacer>
      <PersonalDetails />
      <NotificationSettings :notification-options="notificationOptions" :user-notifications="userNotifications" />
      <SecuritySettings v-if="tenant.allow_password_auth" :has-password="hasPassword" />
    </Spacer>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import PersonalDetails from '@/components/forms/form-sets/PersonalDetails.vue'
import SecuritySettings from '@/components/forms/form-sets/SecuritySettings.vue'
import Spacer from '@/components/Spacer.vue'
import NotificationSettings from '@/components/forms/form-sets/NotificationSettings.vue'

const props = defineProps({
  user: Object,
  tenant: Object,
  hasPassword: Boolean,
  notificationOptions: Array,
  userNotifications: [Object, Array],
})
</script>
