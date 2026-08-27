<script setup lang="ts">
import {
  ref
} from 'vue'
import MainLayout from '@/layouts/MainLayout.vue'
import ThemeSwitcher from '@/components/theme/ThemeSwitcher.vue'
import PostComposer from '@/components/post/PostComposer.vue'
import PostCard from '@/components/post/PostCard.vue'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  Post,
} from '@/types/post'

const authStore = useAuthStore()

const createdPosts = ref<Post[]>([])

function handlePostCreated(
  post: Post,
): void {
  createdPosts.value.unshift(post)
}

function handlePostUpdated(
  updatedPost: Post,
): void {
  const index =
    createdPosts.value
      .findIndex(
        (post) =>
          post.id ===
          updatedPost.id,
      )

  if (index === -1) {
    return
  }

  createdPosts.value[index] =
    updatedPost
}
</script>

<template>
  <MainLayout>
    <section class="feed-page">
      <header class="feed-header">
        <h1 class="feed-header__title">
          Trang chủ
        </h1>

        <ThemeSwitcher />
      </header>

      <PostComposer
        v-if="authStore.isAuthenticated"
        @created="handlePostCreated"
      />
      <div
        v-else
        class="home-auth-notice"
      >
        Đăng nhập để tạo bài viết.
      </div>

      <section
        v-if="createdPosts.length > 0"
        class="home-post-list"
      >
        <PostCard
          v-for="post in createdPosts"
          :key="post.id"
          :post="post"
          @updated="handlePostUpdated"
        />
      </section>

      <div
        v-if="
          createdPosts.length === 0
        "
        class="feed-placeholder"
      >
        <h2 class="feed-placeholder__title">
          Feed đang được xây dựng
        </h2>

        <p class="feed-placeholder__description">
          Bài viết vừa tạo sẽ xuất hiện ở đây.
        </p>
      </div>
    </section>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/HomeView.scss"
></style>
