<template>
  <Authenticated>
    <template #actions>
      <ActionWrapper>
        <AppButton size="sm" :loading="syncing" @click.prevent="sync()">
          {{ __('Sync') }}
        </AppButton>
      </ActionWrapper>
    </template>

    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <ActionStat
        v-for="item in counts"
        :key="item.key"
        :value="item.value"
        :loading="uiState === item.key"
        :action-text="__('Sync')"
        :icon="UsersIcon"
        @action="syncItem(item.key)"
      >
        {{ item.label }}
      </ActionStat>
    </dl>
  </Authenticated>
</template>

<script setup>
import { ref } from 'vue'
import Authenticated from '@/layouts/Authenticated.vue'
import AppButton from '@/components/AppButton.vue'
import ActionWrapper from '@/components/ActionWrapper.vue'
import useSisObjectSync from '@/composition/useSisObjectSync.js'
import ActionStat from '@/components/ActionStat.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  school: Object,
  counts: Object,
})
const { syncing, sync } = useSisObjectSync('school', props.school)
const uiState = ref()
const syncItem = userType => {
  uiState.value = userType

  try {
    router.post(`/settings/school/sync/${userType}`, null, {
      preserveScroll: true,
      onFinish: () => {
        uiState.value = null
      },
    })
  } catch (err) {
    console.error(err)
    uiState.value = null
  }
}
</script>
