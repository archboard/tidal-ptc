<template>
  <Authenticated>
    <CardWrapper>
      <CardPadding v-if="localPermissions.permissions && localPermissions.permissions.length > 0">
        <CardHeader>{{ __('General Permissions') }}</CardHeader>
      </CardPadding>
      <CardPadding v-if="localPermissions.permissions && localPermissions.permissions.length > 0">
        <div class="space-y-2">
          <div v-for="permission in localPermissions.permissions" :key="permission.key">
            <AppCheckbox v-model="permission.granted" @change="value => updatePermission(permission.value, value)">
              {{ permission.label }}
            </AppCheckbox>
            <HelpText v-if="permission.description" class="-mt-1">{{ permission.description }}</HelpText>
          </div>
        </div>
      </CardPadding>

      <CardPadding>
        <div class="flex items-center justify-between">
          <CardHeader>{{ __('School Permissions') }}</CardHeader>
          <AppCheckbox v-model="localPermissions.schools[user.school_id].manages" @change="value => updatePermission('*', value, user.school_id, '*', true)">
            {{ __('Grant all permissions for this school') }}
          </AppCheckbox>
        </div>
      </CardPadding>
      <CardPadding>
        <div class="space-y-2">
          <div v-for="permission in localPermissions.schools[user.school_id].permissions" :key="permission.key">
            <AppCheckbox v-model="permission.granted" @change="value => updatePermission(permission.value, value, user.school_id)">
              {{ permission.label }}
            </AppCheckbox>
            <HelpText v-if="permission.description" class="-mt-1">{{ permission.description }}</HelpText>
          </div>
        </div>

        <div class="mt-4 border-t border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600 space-y-4">
          <div v-for="model in localPermissions.schools[user.school_id].models" :key="model.model">
            <div class="pt-4">
              <h3 class="font-medium mb-2">{{ model.label }}</h3>
              <AppCheckbox v-model="model.manages" @change="value => updatePermission('*', value, user.school_id, model.model, true)">
                {{ __('Full access') }}
              </AppCheckbox>
            </div>
            <FadeIn>
              <div v-if="!model.manages" class="flex items-start space-x-4">
                <template v-for="permission in model.permissions" :key="permission.key">
                  <AppCheckbox v-model="permission.granted" @change="value => updatePermission(permission.value, value, user.school_id, model.model)">
                    {{ permission.label }}
                  </AppCheckbox>
                </template>
              </div>
            </FadeIn>
          </div>
        </div>
      </CardPadding>
    </CardWrapper>
  </Authenticated>
</template>

<script setup>
import { inject, ref, watch } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import CardWrapper from '@/components/CardWrapper.vue'
import CardPadding from '@/components/CardPadding.vue'
import CardHeader from '@/components/CardHeader.vue'
import NProgress from 'nprogress'
import AppCheckbox from '@/components/forms/AppCheckbox.vue'
import clone from 'just-clone'
import FadeIn from '@/components/transitions/FadeIn.vue'
import HelpText from '@/components/forms/HelpText.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
  subject: Object,
  permissions: Object,
})
const emit = defineEmits([])
const localPermissions = ref(clone(props.permissions))
const $http = inject('$http')
const updatePermission = async (permission, granted, school = null, model = null, reload = false) => {
  NProgress.start()

  try {
    const { data } = await $http.put(`/users/${props.subject.id}/permissions`, {
      permission,
      granted,
      school,
      model,
    })

    if (reload) {
      return router.reload({
        preserveScroll: true,
        only: ['permissions']
      })
    }
  } catch (error) {
    console.error(error)
  }

  NProgress.done()
}

watch(() => props.permissions, value => {
  localPermissions.value = clone(value)
})
</script>
