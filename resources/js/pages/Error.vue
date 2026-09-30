<template>
  <div>
    <h1>{{ title }}</h1>
    <div>{{ description }}</div>
    <ul v-if="links.length">
      <li v-for="link in links" :key="link.href">
        <a :href="link.href">{{ link.label }}</a>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  props: {
    status: Number,
    message: String,
    links: {
      type: Array,
      default: () => [],
    },
  },

  computed: {
    title () {
      return {
        503: this.__('503: Service Unavailable'),
        500: this.__('500: Server Error'),
        404: this.__('404: Page Not Found'),
        403: this.__('403: Forbidden'),
        402: this.__('402: School license limit reached'),
      }[this.status]
    },

    description () {
      return this.message ?? {
        503: this.__('Sorry, we are doing some maintenance. Please check back soon.'),
        500: this.__('Whoops, something went wrong on our servers.'),
        404: this.__('Sorry, the page you are looking for could not be found.'),
        403: this.__('Sorry, you are forbidden from accessing this page.'),
      }[this.status]
    },
  },
}
</script>
