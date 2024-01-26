<template>
  <Authenticated>
    <Filters
      v-model="filters"
      v-model:search="search"
      :available-filters="availableFilters"
      @update="updateResults()"
      :loading="updatingResults"
    />

    <div v-if="selection.length > 0" class="px-4 sm:px-5 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600 text-sm">
      <div class="flex items-center space-x-2">
        <span>{{ __(':count selected', { count: selection.length }) }}</span>
        <AppLink is="button" @click.prevent="selectedAll = false">{{ __('Remove selection') }}</AppLink>
      </div>
    </div>

    <Table no-top-radius>
      <Thead>
        <tr>
          <Th class="pr-0 w-4"><Checkbox @change="selectedAll = !selectedAll" :checked="selectedAll" /></Th>
          <Th>{{ __('Name') }}</Th>
          <Th>{{ __('Email') }}</Th>
          <Th>{{ __('Type') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="user in users.data" :key="user.id">
          <Td class="pr-0 w-4">
            <Checkbox v-model="selection" :value="user.id" @change="toggleSelection(user.id)" />
          </Td>
          <Td>{{ user.name }}</Td>
          <Td>{{ user.email }}</Td>
          <Td>{{ user.user_type_display }}</Td>
          <ActionColumn>
            <ContextMenu>
              <div class="p-1">
                <AppMenuItem v-if="can('user.view')" is="InertiaLink" :href="`/users/${user.id}`">
                  {{ __('View') }}
                </AppMenuItem>
                <AppMenuItem v-if="can('edit_permissions')" is="InertiaLink" :href="`/users/${user.id}/permissions`">
                  {{ __('Edit permissions') }}
                </AppMenuItem>
              </div>
            </ContextMenu>
          </ActionColumn>
        </tr>

        <tr v-if="users.data.length === 0">
          <Td class="text-center" colspan="5">
            {{ __('No results found.') }}
          </Td>
        </tr>
      </Tbody>
    </Table>

    <Pagination :links="users.links" :meta="users.meta" />
  </Authenticated>
</template>

<script setup>
import Authenticated from '@/layouts/Authenticated.vue'
import { ActionColumn, Table, Tbody, Th, Thead } from '@/components/tables/index.js'
import Td from '@/components/tables/Td.vue'
import Pagination from '@/components/tables/Pagination.vue'
import ContextMenu from '@/components/ContextMenu.vue'
import AppMenuItem from '@/components/AppMenuItem.vue'
import Checkbox from '@/components/forms/Checkbox.vue'
import useModelSelection from '@/composition/useModelSelection.js'
import Filters from '@/components/tables/Filters.vue'
import useFilters from '@/composition/useFilters.js'
import AppLink from '@/components/AppLink.vue'

const props = defineProps({
  users: Object,
  availableFilters: Array,
  currentFilters: [Array, Object],
})
const { selection, selectedAll, toggleSelection, selectNone } = useModelSelection('user')
const { filters, search, updateResults, updatingResults } = useFilters()
</script>
