<script setup lang="ts">
import { computed } from 'vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppDropdown from '@/components/ui/AppDropdown.vue'
import { useThemeStore } from '@/stores/theme'
import type { ThemePreference } from '@/types/theme'

const themeStore = useThemeStore()

const themeLabel = computed(() => {
  switch (themeStore.preference) {
    case 'light':
      return '☀ Sáng'

    case 'dark':
      return '☾ Tối'

    case 'system':
    default:
      return '◐ Hệ thống'
  }
})

function selectTheme(
  theme: ThemePreference,
): void {
  themeStore.setTheme(theme)
}
</script>

<template>
  <div class="theme-switcher">
    <span class="theme-switcher__label">
      Giao diện
    </span>

    <AppDropdown>
      <template #trigger>
        <AppButton
          variant="secondary"
          size="sm"
          class="theme-switcher__trigger"
        >
          {{ themeLabel }}

          <span
            class="theme-switcher__arrow"
            aria-hidden="true"
          >
            ▾
          </span>
        </AppButton>
      </template>

      <button
        type="button"
        class="theme-switcher__option"
        :class="{
          'theme-switcher__option--active':
            themeStore.preference === 'light',
        }"
        @click="selectTheme('light')"
      >
        <span>Sáng</span>

        <span
          v-if="themeStore.preference === 'light'"
          aria-hidden="true"
        >
          ✓
        </span>
      </button>

      <button
        type="button"
        class="theme-switcher__option"
        :class="{
          'theme-switcher__option--active':
            themeStore.preference === 'dark',
        }"
        @click="selectTheme('dark')"
      >
        <span>Tối</span>

        <span
          v-if="themeStore.preference === 'dark'"
          aria-hidden="true"
        >
          ✓
        </span>
      </button>

      <button
        type="button"
        class="theme-switcher__option"
        :class="{
          'theme-switcher__option--active':
            themeStore.preference === 'system',
        }"
        @click="selectTheme('system')"
      >
        <span>Hệ thống</span>

        <span
          v-if="themeStore.preference === 'system'"
          aria-hidden="true"
        >
          ✓
        </span>
      </button>
    </AppDropdown>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/theme/ThemeSwitcher.scss"
></style>
