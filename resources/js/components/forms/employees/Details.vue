<template>
  <form @submit.prevent="saveDetails">
    <CardWrapper>
      <CardPadding class="flex justify-between items-start">
        <Headline3>{{ $t('Personal details') }}</Headline3>
        <AppButton :class="{ 'invisible': editing }" size="sm" @click.prevent="editing = true">{{ $t('Edit') }}</AppButton>
      </CardPadding>
      <CardPadding>
        <div v-if="editing" class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3">
          <FormField v-model="form.first_name" :error="form.errors.first_name" required>
            {{ $t('First name') }}
          </FormField>
          <FormField v-model="form.middle_name" :error="form.errors.middle_name">
            {{ $t('Middle name(s)') }}
            <template #after>
              <HelpText>{{ $t("If they have multiple middle names, include all middle names here.") }}</HelpText>
            </template>
          </FormField>
          <FormField v-model="form.last_name" :error="form.errors.last_name" required>
            {{ $t('Last name') }}
          </FormField>
          <FormField v-model="form.preferred_name" :error="form.errors.preferred_name">
            {{ $t('Preferred name') }}
            <template #after>
              <HelpText>{{ $t("This will override the first name when displaying the employee's name. Leave empty to keep the first name.") }}</HelpText>
            </template>
          </FormField>
          <FormField v-model="form.maiden_name" :error="form.errors.maiden_name">
            {{ $t('Maiden name') }}
          </FormField>
          <FormField v-model="form.native_language_name" :error="form.errors.native_language_name">
            {{ $t('Native language name') }}
            <template #after>
              <HelpText>{{ $t("This needs a description about what it is and what to store here.") }}</HelpText>
            </template>
          </FormField>
          <FormField :error="form.errors.dob" required>
            {{ $t('Date of birth') }}
            <template #component>
              <AppDatepicker v-model="form.dob" />
            </template>
          </FormField>
          <FormField :error="form.errors.gender" required>
            {{ $t('Gender') }}
            <template #component>
              <GenderSelect v-model="form.gender" />
            </template>
          </FormField>
          <FormField :error="form.errors.marital_status" required>
            {{ $t('Martial status') }}
            <template #component>
              <AppSelect v-model="form.marital_status">
                <option value="married">{{ $t('Married') }}</option>
                <option value="single">{{ $t('Single') }}</option>
              </AppSelect>
            </template>
          </FormField>
          <FormField :model-value="employee.email" disabled>
            {{ $t('Email') }}
            <template #after>
              <HelpText>{{ $t("This was assigned to the employee during onboarding and cannot be changed here. Please contact the Helpdesk to change the employee's company email address.") }}</HelpText>
            </template>
          </FormField>
          <FormField v-model="form.personal_email" :error="form.errors.personal_email" type="email">
            {{ $t('Personal email') }}
          </FormField>
        </div>
        <dl v-else class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <Dt>{{ $t('First name') }}</Dt>
            <Dd>{{ employee.first_name }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Middle name') }}</Dt>
            <Dd>{{ employee.middle_name }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Last name') }}</Dt>
            <Dd>{{ employee.last_name }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Preferred name') }}</Dt>
            <Dd>{{ employee.preferred_name }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Maiden name') }}</Dt>
            <Dd>{{ employee.maiden_name }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Native language name') }}</Dt>
            <Dd>{{ employee.native_language_name }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Date of birth (age)') }}</Dt>
            <Dd>{{ employee.dob }} <span v-if="employee.dob">({{ employee.age }})</span></Dd>
          </div>
          <div>
            <Dt>{{ $t('Gender') }}</Dt>
            <Dd>{{ employee.gender_formatted }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Marital status') }}</Dt>
            <Dd>{{ employee.marital_status_formatted }}</Dd>
          </div>
          <div>
            <Dt>{{ $t('Email') }}</Dt>
            <Dd>
              <AppLink is="a" :href="`mailto:${employee.email}`">
                {{ employee.email }}
              </AppLink>
            </Dd>
          </div>
          <div>
            <Dt>{{ $t('Personal email') }}</Dt>
            <Dd>
              <AppLink v-if="employee.personal_email" is="a" :href="`mailto:${employee.personal_email}`">
                {{ employee.personal_email }}
              </AppLink>
              <span v-else>N/A</span>
            </Dd>
          </div>
        </dl>
      </CardPadding>
    </CardWrapper>
    <FormActions v-if="editing" class="mt-6">
      <AppButton @click.prevent="editing = false" color="white">
        {{ $t('Cancel') }}
      </AppButton>
      <AppButton type="submit" :loading="form.processing">
        {{ $t('Save') }}
      </AppButton>
    </FormActions>
  </form>
</template>

<script setup>
import Headline3 from '@/components/Headline3.vue'
import Dt from '@/components/Dt.vue'
import Dd from '@/components/Dd.vue'
import AppLink from '@/components/AppLink.vue'
import AppButton from '@/components/AppButton.vue'
import { ref } from 'vue'
import FormField from '@/components/forms/FormField.vue'
import { useForm } from '@inertiajs/vue3'
import FormActions from '@/components/forms/FormActions.vue'
import AppDatepicker from '@/components/forms/AppDatepicker.vue'
import GenderSelect from '@/components/forms/GenderSelect.vue'
import HelpText from '@/components/forms/HelpText.vue'
import AppSelect from '@/components/forms/AppSelect.vue'

const props = defineProps({
  employee: Object,
  attributes: Array,
})
const editing = ref(false)
const form = useForm(props.attributes.reduce((carry, a) => {
  carry[a] = props.employee[a] || null
  return carry
}, {}))
const saveDetails = () => {
  form.put(`/employees/${props.employee.id}/details`, {
    onSuccess: () => {
      editing.value = false
    }
  })
}
</script>
