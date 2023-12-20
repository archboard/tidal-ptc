<template>
  <form @submit.prevent="save">
    <SplitForm :loading="form.processing">
      <template #headline>
        <Headline3>{{ __('Translator settings') }}</Headline3>
        <HelpText>{{ __('Configure languages for translators and translator requests.') }}</HelpText>
      </template>

      <div class="col-span-6 space-y-3 divide-y divide-gray-200 dark:divide-gray-600">
        <FadeInGroup>
          <div v-for="(language, index) in form.languages" :key="language.code" class="pt-3">
            <div class="grid grid-cols-6 gap-6">
              <FormField v-model="language.id" :error="form.errors[`languages.${index}.code`]" component="AppSelect" :options="getLanguages(language.id)" class="col-span-6 sm:col-span-2">
                {{ __('Language') }}
              </FormField>

              <FormField v-model="language.request_max" :help="__('Set the maximum number of translator requests for this language.')" :error="form.errors[`languages.${index}.request_max`]" type="number" class="col-span-6 sm:col-span-2">
                {{ __('Maximum requests') }}
              </FormField>

              <FormField v-model="language.overlap_max" :help="__('Set the number of requests for this language can overlap.')" :error="form.errors[`languages.${index}.overlap_max`]" type="number" class="col-span-6 sm:col-span-2">
                {{ __('Maximum overlaps') }}
              </FormField>

              <div class="col-span-6 flex justify-end">
                <AppButton size="sm" color="gray" @click.prevent="form.languages.splice(index, 1)">
                  <TrashIcon class="h-4 w-4" />
                  <span>{{ __('Remove') }}</span>
                </AppButton>
              </div>
            </div>
          </div>
        </FadeInGroup>

        <div class="pt-4">
          <AppButton @click.prevent="addLanguage">
            <PlusIcon class="h-4 w-4" />
            <span>{{ __('Add language') }}</span>
          </AppButton>
        </div>
      </div>
    </SplitForm>
  </form>
</template>

<script setup>
import SplitForm from '@/components/SplitForm.vue'
import { useForm } from '@inertiajs/vue3'
import Headline3 from '@/components/Headline3.vue'
import HelpText from '@/components/forms/HelpText.vue'
import useLanguages from '@/composition/useLanguages.js'
import clone from 'just-clone'
import FadeInGroup from '@/components/transitions/FadeInGroup.vue'
import { nanoid } from 'nanoid'
import AppButton from '@/components/AppButton.vue'
import FormField from '@/components/forms/FormField.vue'
import { TrashIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  school: Object,
})
const emit = defineEmits([])
const form = useForm({
  languages: clone(props.school.languages) || [],
})
const languages = useLanguages()
const save = () => {
  form.put('/settings/school/languages', {
    preserveScroll: true,
  })
}
const addLanguage = () => {
  form.languages.push({
    id: null,
    code: nanoid(2),
    request_max: null,
    overlap_max: null,
  })
}
const getLanguages = except => {
  return languages.value.filter(language => {
    return !form.languages.find(item => item.id === language.id && item.id !== except)
  })
}
</script>
