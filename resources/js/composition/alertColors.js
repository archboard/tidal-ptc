import { computed } from 'vue'

const bgColors = {
  success: 'bg-green-50 dark:bg-green-900',
  neutral: 'bg-gray-50 dark:bg-gray-900',
  error: 'bg-red-50 dark:bg-red-900',
  warning: 'bg-yellow-50 dark:bg-yellow-900',
}
const iconColors = {
  success: 'text-green-400',
  neutral: 'text-gray-400',
  error: 'text-red-400',
  warning: 'text-yellow-400',
}
const textColors = {
  success: 'text-green-800 dark:text-green-100',
  neutral: 'text-gray-800 dark:text-gray-100',
  error: 'text-red-800 dark:text-red-100',
  warning: 'text-yellow-800 dark:text-yellow-100',
}
const dismissColors = {
  success: 'bg-green-50 dark:bg-green-900 text-green-500 hover:bg-green-100 dark:hover:bg-green-800 focus:ring-offset-green-50 dark:focus:ring-offset-green-900 focus:ring-green-600',
  neutral: 'bg-gray-50 dark:bg-gray-900 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-900 focus:ring-offset-gray-50 dark:focus:ring-offset-gray-800 focus:ring-gray-600',
  error: 'bg-red-50 dark:bg-red-900 text-red-500 hover:bg-red-100 dark:hover:bg-red-800 focus:ring-offset-red-50 dark:focus:ring-offset-red-900 focus:ring-red-600',
  warning: 'bg-yellow-50 dark:bg-yellow-900 text-yellow-500 hover:bg-yellow-100 dark:hover:bg-yellow-800 focus:ring-offset-yellow-50 dark:focus:ring-offset-yellow-900 focus:ring-yellow-600',
}

export default (level) => {
  return {
    iconColor: computed(() => iconColors[level] || iconColors.neutral),
    bgColor: computed(() => bgColors[level] || bgColors.neutral),
    textColor: computed(() => textColors[level] || textColors.neutral),
    dismissColor: computed(() => dismissColors[level] || dismissColors.neutral),
  }
}
