<template>
  <FormField :error="error" :help="helpText" :required="required">
    {{ label }}
    <template #component>
      <FileUpload
        v-model="localValue"
        drag-and-drop
        multiple
        @added="uploadFiles"
        @removed="removeFile"
        :disabled="disabled"
        @download="download"
        :hide-download="options?.hideDownload"
        :hide-drop="localValue.length >= max"
      />
    </template>
  </FormField>
</template>

<script setup>
import { fieldProps, fieldEmits } from '@/composition/useFormField.js'
import FormField from '@/components/forms/FormField.vue'
import { useVModel } from '@vueuse/core'
import { inject, ref, computed } from 'vue'
import usesApplicant from '@/composition/usesApplicant.js'
import usesUser from '@/composition/usesUser.js'
import useProp from '@/composition/useProp.js'
import FileUpload from '@/components/forms/FileUpload.vue'

const props = defineProps({
  ...fieldProps,
})
const emit = defineEmits([
  ...fieldEmits,
])
const localValue = useVModel(props, 'modelValue', emit)
const getFileName = path => (path || 'file').replace(/^.*[\\\/]/, '')
const applicantForm = useProp('applicantForm')
const url = computed(() => {
  return applicant.value
    ? `/applicants/${applicant.value.id}/forms/${applicantForm.value?.id}/upload/${props.field?.id}`
    : `/forms/${applicantForm.value?.id}/upload/${props.field?.id}`
})
const max = ref(props.field.options?.max || Infinity)
const applicant = usesApplicant()
const user = usesUser()
const $error = inject('$error')
const $success = inject('$success')
const $http = inject('$http')

const valueMap = value => {
  if (value?.value) {
    value.value = getFileName(value.value)
  }

  return value
}
const download = file => {
  if (file.id) {
    window.location.href = `/files/${file.id}/download`
  }
}
const uploadFiles = async (selectedFiles) => {
  if (
    props.disabled ||
    localValue.value.length >= max.value
  ) {
    return
  }

  for (const file of selectedFiles) {
    if (props.options?.uploadManually) {
      file.uploading = false
      localValue.value.push(file)
      continue
    }

    const config = {
      onUploadProgress: progressEvent => {
        file.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    }

    const form = new FormData()
    form.append('file', file.file)

    try {
      const { data } = await $http.post(url.value, form, config)
      localValue.value.push(valueMap(data))

      $success(`${file.name} uploaded successfully`)
    } catch (err) {
      $error(err.message)
    }
  }
}
const removeFile = async file => {
  const index = localValue.value.findIndex(v => v.id === file.id)

  if (props.options?.uploadManually && index > -1) {
    localValue.value.splice(index, 1)
    return
  }

  try {
    await $http.delete(`/files/${file.id}`)

    if (index > -1) {
      localValue.value.splice(index, 1)
    }

    $success('File deleted successfully')
  } catch (err) {
    $error(err.message)
  }
}
</script>
