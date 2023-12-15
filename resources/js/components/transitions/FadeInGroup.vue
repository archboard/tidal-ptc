<template>
  <transition-group
    enter-active-class="transition duration-150 delay-150 ease-in-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in-out"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <slot/>
  </transition-group>
</template>
