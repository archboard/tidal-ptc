<template>
  <dl v-if="rows.length" class="text-xs space-y-0.5">
    <div v-for="row in rows" :key="row.key" class="flex gap-1">
      <dt class="text-gray-500 dark:text-gray-300 shrink-0">{{ row.key }}:</dt>
      <dd class="break-all">
        <template v-if="row.old !== undefined"><s class="text-gray-400 dark:text-gray-300">{{ fmt(row.old) }}</s> → </template>{{ fmt(row.value) }}
      </dd>
    </div>
  </dl>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  activity: Object,
})
const fmt = v => v === null || v === undefined || v === '' ? '—' : typeof v === 'object' ? JSON.stringify(v) : String(v)
const rows = computed(() => {
  const changes = props.activity.changes ?? {}
  const changed = Object.entries(changes.attributes ?? changes.old ?? {}).map(([key, value]) => ({
    key,
    value: changes.attributes ? value : undefined,
    old: changes.old?.[key],
  }))
  const props_ = Object.entries(props.activity.properties ?? {}).map(([key, value]) => ({ key, value }))
  return [...changed, ...props_]
})
</script>
