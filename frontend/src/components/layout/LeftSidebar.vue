<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
</script>

<template>
  <aside class="left-sidebar">
    <div class="left-sidebar__inner">
      <RouterLink
        to="/"
        class="brand"
      >
        <span class="brand__full">
          SocialX
        </span>

        <span class="brand__compact">
          SX
        </span>
      </RouterLink>

      <nav class="main-navigation">
        <RouterLink
          to="/"
          class="main-navigation__item"
        >
          <span class="main-navigation__icon">
            H
          </span>

          <span class="main-navigation__label">
            Trang chủ
          </span>
        </RouterLink>

        <span class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon">
            K
          </span>

          <span class="main-navigation__label">
            Khám phá
          </span>
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon">
            T
          </span>

          <span class="main-navigation__label">
            Thông báo
          </span>
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon">
            M
          </span>

          <span class="main-navigation__label">
            Tin nhắn
          </span>
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon">
            B
          </span>

          <span class="main-navigation__label">
            Dấu trang
          </span>
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon">
            P
          </span>

          <span class="main-navigation__label">
            Hồ sơ
          </span>
        </span>
      </nav>

      <AppButton
        class="create-post-button"
        size="lg"
        block
        disabled
      >
        <span class="create-post-button__full">
          Đăng bài
        </span>

        <span class="create-post-button__compact">
          +
        </span>
      </AppButton>
    </div>
  </aside>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/layout/LeftSidebar.scss"
></style>
