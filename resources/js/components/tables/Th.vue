<template>
  <th scope="col" :class="['px-6 py-3 text-xs font-medium text-black dark:text-gray-100 tracking-wider', right ? 'text-end' : 'text-start']">
    <slot/>
  </th>
</template>

<script setup>
defineProps({
  right: {
    type: Boolean,
    default: false,
  }
})
</script>
