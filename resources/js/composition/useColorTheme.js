import { useDark, useToggle  } from '@vueuse/core'

export default function useColorTheme() {
  // valueLight writes `.light` so an explicit choice overrides the OS fallback in app.css
  const isDark = useDark({ valueLight: 'light' })
  const toggleTheme = useToggle(isDark)

  return {
    isDark,
    toggleTheme,
  }
}
