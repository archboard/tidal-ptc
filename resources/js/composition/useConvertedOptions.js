import { computed } from 'vue'
import reduce from 'just-reduce-object'

export default options => {
  return computed(() => {
    if (Array.isArray(options)) {
      return options.map(option => ({
        value: option.value || option.id || option.label,
        label: option.label || option.name,
      }))
    }

    if (typeof options === 'object') {
      return reduce(options, (carry, key, value) => {
        carry.push({
          value: isNaN(key) ? key : parseInt(key, 10),
          label: value,
        })

        return carry
      }, [])
    }

    return []
  })
}
