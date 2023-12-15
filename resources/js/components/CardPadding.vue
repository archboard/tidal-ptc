<template>
  <div :class="size">
    <slot />
  </div>
</template>

<script setup>
const props = defineProps({
  size: {
    type: String,
    default: 'base',
  }
})
const sizes = {
  // sm: 'px-4 py-3 sm:px-6',
  base: 'py-5 px-5',
}
const size = sizes[props.size] || sizes.base
</script>

