import { computed, toValue } from 'vue'
import { useLocalStorage, useColorMode } from '@vueuse/core'
import themeStore from '@/stores/theme.js'

export default function useColorTheme() {
  const theme = useLocalStorage('theme', toValue(useColorMode()))
  themeStore.state.value = theme.value

  const toggleTheme = () => {
    theme.value = toValue(isDark) ? 'light' : 'dark'
    themeStore.state.value = theme.value
    window.changeTheme(toValue(isDark))
  }
  const isDark = computed(() => {
    return themeStore.isDark()
  })

  return {
    theme,
    isDark,
    toggleTheme,
  }
}
