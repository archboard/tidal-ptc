<template>
  <transition
    enter-active-class="ease-out duration-300"
    enter-from-class="opacity-0 -translate-y-5"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-5"
  >
    <slot />
  </transition>
</template>
