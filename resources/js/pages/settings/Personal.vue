<template>
  <Authenticated>
    <Spacer>
      <PersonalDetails />
      <SecuritySettings v-if="tenant.allow_password_auth" :has-password="hasPassword" />
    </Spacer>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import PersonalDetails from '@/components/forms/form-sets/PersonalDetails.vue'
import SecuritySettings from '@/components/forms/form-sets/SecuritySettings.vue'
import Spacer from '@/components/Spacer.vue'

const props = defineProps({
  user: Object,
  tenant: Object,
  hasPassword: Boolean,
})
</script>
