import { CheckCircleIcon, InformationCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/solid/index.js'

const icons = {
  success: CheckCircleIcon,
  neutral: InformationCircleIcon,
  warning: ExclamationCircleIcon,
  error: ExclamationCircleIcon,
}

export default {
  props: {
    level: {
      type: String,
      default: 'neutral',
    },
    notDismissible: {
      type: Boolean,
      default: false,
    }
  },

  computed: {
    icon () {
      return icons[this.level] || icons.neutral
    }
  }
}
