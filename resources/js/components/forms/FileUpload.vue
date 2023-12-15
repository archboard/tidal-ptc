<template>
  <ul
    v-if="files.length > 0"
    class="border border-gray-300 rounded-md divide-y divide-gray-300"
    :class="{
      'mb-5': files.length > 0 && (multiple || !dragAndDrop)
    }"
  >
    <li
      v-for="(file, index) in files"
      :key="file.name"
      class="pl-3 pr-4 py-3 flex items-center justify-between text-sm"
    >
      <div class="w-0 flex-1 flex items-center">
        <PaperClipIcon class="flex-shrink-0 h-5 w-5 text-slate-400" aria-hidden="true" />
        <span class="ml-2 flex-1 w-0 flex items-center space-x-2">
          <span class="truncate">{{ file.name }}</span> <Spinner v-if="animateLoading && file.uploading" class="w-5 h-5" />
        </span>
      </div>
      <div class="ml-4 flex-shrink-0">
        <a @click.prevent="removeFile(file, index)" href="#" class="font-medium text-red-500 hover:text-red-400 transition">
          {{ __('Remove') }}
        </a>
      </div>
    </li>
  </ul>

  <div
    v-if="files.length === 0 || multiple"
    class="flex justify-center px-6 pt-5 pb-6 border-4 rounded-md"
    :class="[dragAndDrop ? dragClasses : 'hidden']"
    @dragover.prevent="dragClasses = `bg-gray-100 border-gray-500`"
    @dragleave.prevent="dragClasses = defaultDragClass"
    @drop.prevent="handleDrop"
  >
    <div class="space-y-1 text-center">
      <svg class="mx-auto h-12 w-12 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
      </svg>
      <div class="flex text-sm text-gray-500">
        <label :for="id" class="relative cursor-pointer bg-transparent rounded-md font-medium text-primary-500 hover:text-primary-400 focus-within:outline-none transition">
          <span>{{ multiple ? __('Upload some files') : __('Upload a file') }}</span>
        </label>
        <p class="pl-1">{{ __('or drag and drop') }}</p>
      </div>
      <p v-if="extensions.length > 0" class="text-xs text-gray-500">
        {{ extensions.join(', ') }}
      </p>
    </div>
  </div>
  <input ref="fileElement" :id="id" :name="`file-upload-${id}`" type="file" class="sr-only" :multiple="multiple" @change="fileSelected" />

  <AppButton v-if="!dragAndDrop" @click.prevent="triggerDialog">
    {{ __('Select file') }}
  </AppButton>
</template>

<script>
import { defineComponent, inject, ref, watch } from 'vue'
import { nanoid } from 'nanoid'
import { PaperClipIcon } from '@heroicons/vue/24/solid'
import AppButton from '@/components/AppButton.vue'
import Spinner from '@/components/Spinner.vue'
import last from 'just-last'

export default defineComponent({
  components: {
    Spinner,
    AppButton,
    PaperClipIcon,
  },

  props: {
    extensions: {
      type: Array,
      default: [],
    },
    errors: {
      type: Object,
      default: () => {},
    },
    modelValue: [Object, String],
    dragAndDrop: {
      type: Boolean,
      default: false,
    },
    id: {
      type: String,
      default: 'file-upload',
    },
    multiple: {
      type: Boolean,
      default: () => false,
    },
    animateLoading: {
      type: Boolean,
      default: () => false,
    }
  },
  emits: ['update:modelValue', 'added', 'removed'],

  setup (props, { emit }) {
    const $error = inject('$error')
    const $translate = inject('$translate')
    const fileElement = ref()
    const files = ref(props.modelValue || [])
    const defaultDragClass = 'border-gray-200 border-dashed'
    const dragClasses = ref(defaultDragClass)
    const fileSelected = e => {
      const chosenFiles = Array.from(e.target.files).map(fileMap)
      files.value = chosenFiles
      emit('added', chosenFiles)
    }
    const handleDrop = e => {
      dragClasses.value = defaultDragClass
      const dataFiles = e.dataTransfer.items || e.dataTransfer.files

      try {
        const chosenFiles = Array.from(dataFiles)
          .map(item => typeof item.getAsFile === 'function' ? item.getAsFile() : item)
          .filter(fileFilter)
          .map(fileMap)
          .filter(item => item.id)

        if (chosenFiles.length > 0) {
          emit('added', chosenFiles)

          files.value = props.multiple
            ? [...files.value, ...chosenFiles]
            : [chosenFiles.shift()]
        }
      } catch (err) {
        $error($translate('Could not choose file: :message', {
          message: err.message
        }))
      }
    }
    const hasValidExtension = extension => {
      if (props.extensions.length === 0) {
        return true
      }

      return props.extensions.map(ex => ex.toLowerCase())
          .includes(extension.toLowerCase())
    }
    const fileFilter = file => {
      if (!file.type) {
        $error($translate('Please select a valid file.'))
        return false
      }

      const extension = last(file.name.split('.'))

      if (!hasValidExtension(extension)) {
        $error($translate('Invalid file extension (:extensions only).', { extensions: props.extensions.join(', ') }))
        return false
      }

      return true
    }
    const fileMap = file => ({
      file,
      name: file.name,
      uploading: true,
      id: `file_${nanoid()}`,
    })
    const removeFile = (file, index) => {
      emit('removed', files.value.splice(index, 1)[0])
    }
    const triggerDialog = () => {
      if (fileElement.value) {
        fileElement.value.click()
      }
    }
    const clear = () => {
      files.value = []
    }
    watch(() => [...files.value], () => {
      emit('update:modelValue', files.value)
    })

    return {
      fileSelected,
      fileElement,
      files,
      dragClasses,
      handleDrop,
      defaultDragClass,
      removeFile,
      triggerDialog,
      clear,
    }
  }
})
</script>
