<template>
  <div class="flex justify-center py-12">
    <div class="max-w-md w-full mx-auto">
      <form @submit.prevent="form.post(endpoint)">
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
import { useForm } from '@inertiajs/vue3'
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
