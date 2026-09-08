<script setup lang="ts">
import { computed, ref } from 'vue'

import { RouterLink } from 'vue-router'

import AppButton from '@/components/ui/AppButton.vue'
import PostComposer from '@/components/post/PostComposer.vue'

import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const postModalOpen = ref(false)

const profilePath = computed(() => {
  if (!authStore.user?.username) {
    return null
  }

  return `/@${authStore.user.username}`
})

function openPostModal(): void {
  postModalOpen.value = true
}

function closePostModal(): void {
  postModalOpen.value = false
}

function handlePostCreated(): void {
  closePostModal()
}
</script>

<template>
  <aside class="left-sidebar">
    <div class="left-sidebar__inner">
      <RouterLink to="/" class="brand">
        <span class="brand__full"> SocialX </span>

        <span class="brand__compact"> SX </span>
      </RouterLink>

      <nav class="main-navigation">
        <RouterLink to="/" class="main-navigation__item">
          <span class="main-navigation__icon"> H </span>

          <span class="main-navigation__label"> Trang chủ </span>
        </RouterLink>

        <RouterLink to="/explore" class="main-navigation__item">
          <span class="main-navigation__icon"> K </span>

          <span class="main-navigation__label"> Khám phá </span>
        </RouterLink>

        <RouterLink to="/notifications" class="main-navigation__item">
          <span class="main-navigation__icon"> T </span>

          <span class="main-navigation__label"> Thông báo </span>
        </RouterLink>

        <span class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon"> M </span>

          <span class="main-navigation__label"> Tin nhắn </span>
        </span>

        <RouterLink to="/bookmarks" class="main-navigation__item">
          <span class="main-navigation__icon"> D </span>

          <span class="main-navigation__label"> Dấu trang </span>
        </RouterLink>

        <RouterLink v-if="profilePath" :to="profilePath" class="main-navigation__item">
          <span class="main-navigation__icon"> P </span>

          <span class="main-navigation__label"> Hồ sơ </span>
        </RouterLink>

        <span v-else class="main-navigation__item main-navigation__item--disabled">
          <span class="main-navigation__icon"> P </span>

          <span class="main-navigation__label"> Hồ sơ </span>
        </span>
      </nav>

      <AppButton class="create-post-button" size="lg" block @click="openPostModal">
        <span class="create-post-button__full"> Đăng bài </span>

        <span class="create-post-button__compact"> + </span>
      </AppButton>
    </div>

    <Teleport to="body">
      <div v-if="postModalOpen" class="create-post-modal" @click.self="closePostModal">
        <div
          class="create-post-modal__dialog"
          role="dialog"
          aria-modal="true"
          aria-label="Đăng bài"
        >
          <div class="create-post-modal__header">
            <button
              type="button"
              class="create-post-modal__close"
              aria-label="Đóng"
              @click="closePostModal"
            >
              ×
            </button>
          </div>

          <PostComposer @created="handlePostCreated" />
        </div>
      </div>
    </Teleport>
  </aside>
</template>

<style lang="scss" src="@/assets/styles/components/layout/LeftSidebar.scss"></style>
