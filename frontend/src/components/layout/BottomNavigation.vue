<script setup lang="ts">
import { RouterLink } from 'vue-router'
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

    <button
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
