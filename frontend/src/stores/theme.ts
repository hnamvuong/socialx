import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import type {
  ResolvedTheme,
  ThemePreference,
} from '@/types/theme'

const STORAGE_KEY = 'socialx_theme'

const DARK_MODE_QUERY =
  '(prefers-color-scheme: dark)'

export const useThemeStore = defineStore(
  'theme',
  () => {
    const preference =
      ref<ThemePreference>('system')

    const systemTheme =
      ref<ResolvedTheme>('light')

    let mediaQuery:
      | MediaQueryList
      | null = null

    const resolvedTheme =
      computed<ResolvedTheme>(() => {
        if (preference.value === 'system') {
          return systemTheme.value
        }

        return preference.value
      })

    function readStoredPreference():
      ThemePreference {
      const stored =
        localStorage.getItem(STORAGE_KEY)

      if (
        stored === 'light' ||
        stored === 'dark' ||
        stored === 'system'
      ) {
        return stored
      }

      return 'system'
    }

    function readSystemTheme():
      ResolvedTheme {
      return window.matchMedia(
        DARK_MODE_QUERY,
      ).matches
        ? 'dark'
        : 'light'
    }

    function applyTheme(): void {
      document.documentElement.dataset.theme =
        resolvedTheme.value

      document.documentElement.style.colorScheme =
        resolvedTheme.value
    }

    function handleSystemThemeChange(
      event: MediaQueryListEvent,
    ): void {
      systemTheme.value =
        event.matches
          ? 'dark'
          : 'light'

      if (preference.value === 'system') {
        applyTheme()
      }
    }

    function setTheme(
      value: ThemePreference,
    ): void {
      preference.value = value

      localStorage.setItem(
        STORAGE_KEY,
        value,
      )

      applyTheme()
    }

    function initialize(): void {
      preference.value =
        readStoredPreference()

      systemTheme.value =
        readSystemTheme()

      mediaQuery = window.matchMedia(
        DARK_MODE_QUERY,
      )

      mediaQuery.addEventListener(
        'change',
        handleSystemThemeChange,
      )

      applyTheme()
    }

    return {
      preference,
      resolvedTheme,
      initialize,
      setTheme,
    }
  },
)
