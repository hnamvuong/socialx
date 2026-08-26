<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <aside class="left-sidebar">
    <div class="left-sidebar__inner">
      <RouterLink
        to="/"
        class="brand"
      >
        SocialX
      </RouterLink>

      <nav class="main-navigation">
        <RouterLink
          to="/"
          class="main-navigation__item"
        >
          Trang chủ
        </RouterLink>

        <span class="main-navigation__item main-navigation__item--disabled">
          Khám phá
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          Thông báo
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          Tin nhắn
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          Dấu trang
        </span>

        <span class="main-navigation__item main-navigation__item--disabled">
          Hồ sơ
        </span>
      </nav>

      <button
        type="button"
        class="create-post-button"
        disabled
      >
        Đăng bài
      </button>
    </div>
  </aside>
</template>
