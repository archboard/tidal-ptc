export const fieldProps = {
  modelValue: [Object, String, Number],
  label: String,
  type: String,
  options: [Array, Object],
  error: String,
  placeholder: String,
  disabled: Boolean,
  required: {
    type: Boolean,
    default: () => false,
  },
  helpText: {
    type: String,
    default: '',
  },
  hasError: {
    type: Boolean,
    default: () => false,
  }
}

export const fieldEmits = ['update:modelValue']
