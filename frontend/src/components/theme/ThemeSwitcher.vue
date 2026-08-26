<script setup lang="ts">
import { useThemeStore } from '@/stores/theme'

import type {
  ThemePreference,
} from '@/types/theme'

const themeStore = useThemeStore()

function handleChange(
  event: Event,
): void {
  const target =
    event.target as HTMLSelectElement

  themeStore.setTheme(
    target.value as ThemePreference,
  )
}
</script>

<template>
  <label class="theme-switcher">
    <span class="theme-switcher__label">
      Giao diện
    </span>

    <select
      class="theme-switcher__select"
      :value="themeStore.preference"
      @change="handleChange"
    >
      <option value="light">
        Sáng
      </option>

      <option value="dark">
        Tối
      </option>

      <option value="system">
        Hệ thống
      </option>
    </select>
  </label>
</template>
