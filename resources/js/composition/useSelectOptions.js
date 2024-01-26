import { computed, toValue } from 'vue'

export default function useSelectOptions (reactiveOptions) {
  return computed(() => {
    const options = toValue(reactiveOptions)

    if (!options) {
      return []
    }

    if (Array.isArray(options)) {
      return options.map(option => {
        if (typeof option === 'string') {
          return {
            label: option,
            value: option,
          }
        }

        return {
          value: option.value || option.id || option.label,
          label: option.label || option.name,
        }
      })
    }

    return Object.keys(options).map(key => {
      return {
        value: key,
        label: options[key],
      }
    })
  })
}
