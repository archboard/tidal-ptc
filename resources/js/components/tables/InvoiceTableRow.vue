<template>
  <tr>
    <slot name="prepend" />
    <Td>
      <span class="whitespace-nowrap flex items-center space-x-2">
        <span>{{ invoice.invoice_number }}</span>
        <CollectionIcon v-if="invoice.is_parent" class="w-4 h-4" />
        <TableLink v-if="invoice.parent" :href="`/invoices/${invoice.parent_uuid}`">{{ invoice.parent.invoice_number }}</TableLink>
      </span>
    </Td>
    <Td :lighter="false">
      <div class="flex items-center space-x-1.5">
        <TableLink :href="`/invoices/${invoice.uuid}`" class="whitespace-nowrap">
          {{ invoice.title }}
        </TableLink>
        <InvoiceStatusBadge :invoice="invoice" size="sm" />
      </div>
    </Td>
    <Td :lighter="false" v-if="can('students.viewAny') && showStudent">
      <InertiaLink v-if="invoice.student" :href="`/students/${invoice.student_uuid}`" class="hover:underline">
        {{ invoice.student.full_name }}
      </InertiaLink>

      <template
        v-for="(student, index) in invoice.students"
        :key="student.uuid"
      >
        <TableLink :href="`/students/${student.uuid}`" class="whitespace-nowrap">
          {{ student.full_name }}
        </TableLink><span v-if="index !== invoice.students.length - 1">, </span>
      </template>
    </Td>
<!--    <Td class="text-right">{{ invoice.amount_due_formatted }}</Td>-->
    <Td class="text-right">{{ invoice.remaining_balance_formatted }}</Td>
    <Td class="text-right space-x-2 pl-0">
      <VerticalDotMenu>
        <InvoiceActionItems
          :invoice="invoice"
          :show-view="true"
          @edit-status="$emit('editStatus', invoice)"
          @convert-to-template="$emit('convertToTemplate', invoice)"
        />
      </VerticalDotMenu>
    </Td>
  </tr>
</template>

<script>
import { defineComponent } from 'vue'
import InvoiceStatusBadge from '@/components/InvoiceStatusBadge'
import Td from '@/components/tables/Td'
import VerticalDotMenu from '@/components/dropdown/VerticalDotMenu'
import InvoiceActionItems from '@/components/dropdown/InvoiceActionItems'
import checksPermissions from '@/composition/checksPermissions'
import TableLink from '@/components/tables/TableLink'
import { CollectionIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    TableLink,
    InvoiceActionItems,
    VerticalDotMenu,
    Td,
    InvoiceStatusBadge,
    CollectionIcon,
  },
  props: {
    invoice: Object,
    showStudent: {
      type: Boolean,
      default: true,
    }
  },
  emits: ['editStatus', 'convertToTemplate'],

  setup () {
    const { can } = checksPermissions()

    return {
      can,
    }
  },
})
</script>
