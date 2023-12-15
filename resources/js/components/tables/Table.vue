<template>
  <div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded" :class="{ 'shadow': !hideShadow }">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
            <slot />
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  hideShadow: {
    type: Boolean,
    default: () => false,
  }
})
</script>
