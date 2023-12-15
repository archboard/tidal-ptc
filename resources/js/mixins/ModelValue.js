export default {
  props: {
    modelValue: {
      type: [Number, String, Object, Boolean, Array],
      default: '',
    },
  },
  emits: ['update:modelValue'],

  computed: {
    localValue: {
      get () {
        return this.modelValue
      },
      set (value) {
        this.$emit('update:modelValue', value)
      }
    }
  },
}
