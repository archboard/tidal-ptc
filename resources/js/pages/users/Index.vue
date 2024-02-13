<template>
  <Authenticated>
    <Filters
      v-model="filters"
      v-model:search="search"
      :available-filters="availableFilters"
      @update="updateResults()"
      :loading="updatingResults"
      :search-placeholder="__('Search by name or email…')"
    />

    <SelectionManager :selection="selection" :select-none="selectNone">
      <div v-if="can('user.update')" class="p-1">
        <AppMenuItem
          is="InertiaLink"
          as="button"
          method="post"
          :href="`/selection/${model_alias}/hidden`"
          :data="{ can_book: false }"
        >
          {{ __('Disable booking') }}
        </AppMenuItem>
        <AppMenuItem
          is="InertiaLink"
          as="button"
          method="post"
          :href="`/selection/${model_alias}/hidden`"
          :data="{ can_book: true }"
        >
          {{ __('Enable booking') }}
        </AppMenuItem>
      </div>
    </SelectionManager>

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
          <Td>
            <div class="flex items-center space-x-2">
              <span class="whitespace-nowrap">{{ user.name }}</span>
              <BookingDisabledPill v-if="!user.can_book" />
            </div>
          </Td>
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
import SelectionManager from '@/components/tables/SelectionManager.vue'
import BookingDisabledPill from '@/components/BookingDisabledPill.vue'

const props = defineProps({
  users: Object,
  availableFilters: Array,
  currentFilters: [Array, Object],
  model_alias: String,
})
const { selection, selectedAll, toggleSelection, selectNone } = useModelSelection('user')
const { filters, search, updateResults, updatingResults } = useFilters()
</script>
