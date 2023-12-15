<template>
  <div>
    <AppCombobox
      v-model="localValue"
      v-model:query="query"
      :options="users"
      :display-value="user => user?.name"
    >
      <template v-slot:item="{ item }">
        {{ item.name }} <span v-if="item.email">({{ item.email }})</span>
      </template>
    </AppCombobox>
  </div>
</template>

<script>
import { computed, defineComponent, inject, ref, watch } from 'vue'
import AppCombobox from '@/components/forms/AppCombobox.vue'
import throttle from 'just-throttle'

export default defineComponent({
  components: {
    AppCombobox,
  },
  emits: ['update:modelValue'],
  props: {
    modelValue: [Object, Number, String],
  },

  setup (props, { emit }) {
    const $http = inject('$http')
    const localValue = computed({
      get: () => props.modelValue,
      set: value => emit('update:modelValue', value)
    })
    const query = ref()
    const users = ref([])
    const search = throttle(async s => {
      if (!s) {
        users.value = []
        return
      }

      try {
        const { data } = await $http.get(`/search/users`, {
          params: { s, limit: 10 }
        })
        users.value = data
      } catch (e) { }
    }, 500, { leading: false })

    watch(query, search)

    return {
      localValue,
      query,
      users,
    }
  }
})
</script>
