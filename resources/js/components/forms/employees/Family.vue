<template>
  <CardWrapper>
    <CardPadding class="flex justify-between items-start">
      <Headline3>{{ $t('Family') }} <span v-if="employee.family?.id">({{ employee.family.name }})</span></Headline3>
      <AppButton size="sm" @click.prevent="state = employee.family?.id ? 'update' : 'create'">{{ $t('Manage family') }}</AppButton>
    </CardPadding>
    <Table>
      <Thead>
        <tr>
          <Th>{{ $t('Name') }}</Th>
          <Th>{{ $t('Age') }}</Th>
          <Th></Th>
        </tr>
      </Thead>
      <Tbody>
        <tr v-for="member in employee.family?.members" :key="member.id">
          <Td darker>
            <div class="flex items-center space-x-2">
              <AppLink :href="`/employees/${member.id}`">
                {{ member.name }}
              </AppLink>
              <Pill v-if="member.is_dependent">{{ $t('Dependent') }}</Pill>
            </div>
          </Td>
          <Td>{{ member.age }}</Td>
          <Td class="text-right">
            <AppLink is="button" @click.prevent="state = `/employees/${member.id}/family`">{{ $t('Remove') }}</AppLink>
          </Td>
        </tr>
        <tr v-if="!employee.family">
          <Td colspan="3" class="text-center">
            {{ $t('No family members are associated with this person.') }}
          </Td>
        </tr>
        <tr v-else-if="!employee.is_dependent">
          <Td colspan="3" class="text-center">
            <AppLink is="button" @click.prevent="state = 'dependent'">
              {{ $t('Add a dependent') }}
            </AppLink>
          </Td>
        </tr>
      </Tbody>
    </Table>
  </CardWrapper>

  <CreateFamilyModal
    v-if="state === 'create'"
    @close="state = null"
    :family="employee.family ?? {}"
    :employee="employee"
  />
  <UpdateFamilyModal
    v-if="state === 'update'"
    @close="state = null"
    :family="employee.family ?? {}"
    :employee="employee"
  />
  <AddDependentModal
    v-if="state === 'dependent'"
    @close="state = null"
    :employee="employee"
  />
  <ConfirmationModal
    v-if="state?.startsWith('/employees/')"
    @close="state = null"
    @confirmed="removeMember"
  >
    {{ $t('Are you sure you want to remove this family member?') }}
  </ConfirmationModal>
</template>

<script>
import { defineComponent, ref } from 'vue'
import Headline3 from '@/components/Headline3.vue'
import AppButton from '@/components/AppButton.vue'
import { Table, Thead, Th, Tbody, Td } from '@/components/tables/index.js'
import AppLink from '@/components/AppLink.vue'
import CreateFamilyModal from '@/components/modals/CreateFamilyModal.vue'
import AddDependentModal from '@/components/modals/AddDependentModal.vue'
import ConfirmationModal from '@/components/modals/ConfirmationModal.vue'
import { router } from '@inertiajs/vue3'
import UpdateFamilyModal from '@/components/modals/UpdateFamilyModal.vue'
import Pill from '@/components/Pill.vue'

export default defineComponent({
  components: {
    Pill,
    UpdateFamilyModal,
    ConfirmationModal,
    AddDependentModal,
    CreateFamilyModal,
    AppLink,
    AppButton,
    Headline3,
    Table,
    Thead,
    Th,
    Td,
    Tbody,
  },
  props: {
    employee: Object,
  },

  setup () {
    const state = ref()
    const removeMember = (close) => {
      router.delete(state.value, {
        onSuccess: () => {
          if (typeof close === 'function') {
            close()
          }
        }
      })
    }

    return {
      state,
      removeMember,
    }
  }
})
</script>
