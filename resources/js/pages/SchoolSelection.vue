<template>
  <div class="flex justify-center py-12">
    <div class="max-w-md w-full mx-auto">
      <CardWrapper v-if="schools.length === 0">
        <CardPadding class="text-center">
          <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h2 class="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('No students found') }}
          </h2>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __("There are no students linked to your account, so there are no schools to choose from. If you believe this is a mistake, please contact your student's school to check your contact information.") }}
          </p>
        </CardPadding>
        <CardAction>
          <AppButton color="white" @click="router.post('/logout')">
            {{ __('Log out') }}
          </AppButton>
        </CardAction>
      </CardWrapper>

      <form v-else @submit.prevent="form.post(endpoint)">
        <CardWrapper>
          <CardPadding>
            <CardHeader>{{ title }}</CardHeader>
          </CardPadding>
          <CardPadding>
              <FormField :error="form.errors.school_id" required>
                {{ __('Choose your current school') }}
                <template #component>
                  <RadioGroup
                    v-model="form.school_id"
                    :options="schoolOptions"
                  />
                </template>
              </FormField>
          </CardPadding>
          <CardAction>
            <AppButton type="submit" :loading="form.processing">
              {{ __('Save') }}
            </AppButton>
          </CardAction>
        </CardWrapper>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { UserGroupIcon } from '@heroicons/vue/24/outline/index.js'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import usePageTitle from '@/composition/usePageTitle.js'
import RadioGroup from '@/components/forms/RadioGroup.vue'
import FormField from '@/components/forms/FormField.vue'
import CardAction from '@/components/CardAction.vue'
import AppButton from '@/components/AppButton.vue'

const props = defineProps({
  schools: Array,
  user: Object,
  title: String,
  endpoint: String,
})
usePageTitle()
const form = useForm({
  school_id: props.user.school_id,
})
const schoolOptions = computed(() => {
  return props.schools.map((school) => {
    return {
      label: school.name,
      value: school.id,
    }
  })
})
</script>
