import { ref, onMounted } from 'vue'

export function useTheme() {
  const theme = ref('dark')

  const applyTheme = (value) => {
    theme.value = value

    if (typeof window === 'undefined') return

    const root = window.document.documentElement

    if (value === 'dark') {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }

    window.localStorage.setItem('theme', value)
  }

  onMounted(() => {
    if (typeof window === 'undefined') return

    const stored = window.localStorage.getItem('theme')
    if (stored === 'light' || stored === 'dark') {
      applyTheme(stored)
      return
    }

    const prefersDark =
      window.matchMedia &&
      window.matchMedia('(prefers-color-scheme: dark)').matches

    applyTheme(prefersDark ? 'dark' : 'light')
  })

  return { theme, applyTheme }
}
