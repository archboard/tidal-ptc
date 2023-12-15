<template>
  <Authenticated>
    <div class="space-y-10">
      <PersonalDetails />
      <SecuritySettings v-if="tenant.allow_password_auth" :has-password="hasPassword" />
    </div>
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import PersonalDetails from '@/components/forms/form-sets/PersonalDetails.vue'
import SecuritySettings from '@/components/forms/form-sets/SecuritySettings.vue'

const props = defineProps({
  user: Object,
  tenant: Object,
  hasPassword: Boolean,
})
</script>
