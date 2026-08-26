<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const profilePath = computed(() => {
  if (!authStore.user?.username) {
    return null
  }

  return `/@${authStore.user.username}`
})
</script>

<template>
  <nav class="bottom-navigation">
    <RouterLink
      to="/"
      class="bottom-navigation__item"
    >
      Trang chủ
    </RouterLink>

    <button
      type="button"
      class="bottom-navigation__item"
      disabled
    >
      Khám phá
    </button>

    <button
      type="button"
      class="bottom-navigation__item"
      disabled
    >
      Thông báo
    </button>

    <RouterLink
      v-if="profilePath"
      :to="profilePath"
      class="bottom-navigation__item"
    >
      Hồ sơ
    </RouterLink>

    <button
      v-else
      type="button"
      class="bottom-navigation__item"
      disabled
    >
      Hồ sơ
    </button>
  </nav>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/layout/BottomNavigation.scss"
></style>
