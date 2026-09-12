<template>
  <div>
    <AppSelect v-model="selected" hide-null :disabled="saving" @change="assign">
      <option :value="null">{{ __('Unassigned') }}</option>
      <option v-for="translator in options" :key="translator.id" :value="translator.id">{{ translator.name }}</option>
    </AppSelect>
    <FieldError v-if="error">{{ error }}</FieldError>
  </div>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { trans as __ } from 'laravel-vue-i18n'
import AppSelect from '@/components/forms/AppSelect.vue'
import FieldError from '@/components/forms/FieldError.vue'

const props = defineProps({
  slot: Object,
  translators: Array,
})
const emit = defineEmits(['assigned'])
const $http = inject('$http')
const selected = ref(props.slot.translator_id ?? null)
const saving = ref(false)
const error = ref(null)
watch(() => props.slot.translator_id, value => selected.value = value ?? null)
// Only translators who speak the requested language; keep the current one listed even if inactive
const options = computed(() => props.translators.filter(t => t.languages.includes(props.slot.language) || t.id === props.slot.translator_id))
const assign = async () => {
  saving.value = true
  error.value = null
  try {
    await $http.put(`/time-slots/${props.slot.id}/translator`, { translator_id: selected.value })
    emit('assigned', selected.value)
  } catch (e) {
    error.value = e.response?.data?.errors?.translator_id?.[0] ?? e.response?.data?.message ?? __('Could not assign translator.')
    selected.value = props.slot.translator_id ?? null
  }
  saving.value = false
}
</script>
