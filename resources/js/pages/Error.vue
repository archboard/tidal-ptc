<template>
  <div>
    <h1>{{ title }}</h1>
    <div>{{ description }}</div>
  </div>
</template>

<script>
export default {
  props: {
    status: Number,
  },

  computed: {
    title () {
      return {
        503: this.__('503: Service Unavailable'),
        500: this.__('500: Server Error'),
        404: this.__('404: Page Not Found'),
        403: this.__('403: Forbidden'),
      }[this.status]
    },

    description () {
      return {
        503: this.__('Sorry, we are doing some maintenance. Please check back soon.'),
        500: this.__('Whoops, something went wrong on our servers.'),
        404: this.__('Sorry, the page you are looking for could not be found.'),
        403: this.__('Sorry, you are forbidden from accessing this page.'),
      }[this.status]
    },
  },
}
</script>
