import { useDark, useToggle  } from '@vueuse/core'

export default function useColorTheme() {
  const isDark = useDark()
  const toggleTheme = useToggle(isDark)

  return {
    isDark,
    toggleTheme,
  }
}
