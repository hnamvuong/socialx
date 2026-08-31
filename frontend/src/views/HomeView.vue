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
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
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

function handlePostDeleted(
  postId: number,
): void {
  createdPosts.value =
    createdPosts.value.filter(
      (post) =>
        post.id !== postId,
    )
}

function handlePostLikeChanged(
  state: PostLikeState,
): void {
  const post =
    createdPosts.value.find(
      (item) =>
        item.id ===
        state.postId,
    )

  if (!post) {
    return
  }

  post.liked_by_me = state.liked

  post.likes_count = state.likesCount
}

function handlePostRepostChanged(
  state: PostRepostState,
): void {
  const post =
    createdPosts.value.find(
      (item) =>
        item.id ===
        state.postId,
    )

  if (!post) {
    return
  }

  post.reposted_by_me =
    state.reposted

  post.reposts_count =
    state.repostsCount
}

function handleQuoteCreated(
  post: Post,
): void {
  createdPosts.value.unshift(
    post,
  )
}

function handlePostBookmarkChanged(
  state: PostBookmarkState,
): void {
  const post =
    createdPosts.value.find(
      (item) =>
        item.id ===
        state.postId,
    )

  if (!post) {
    return
  }

  post.bookmarked_by_me =
    state.bookmarked
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
          @deleted="handlePostDeleted"
          @like-changed="handlePostLikeChanged"
          @repost-changed="handlePostRepostChanged"
          @quote-created="handleQuoteCreated"
          @bookmark-changed="handlePostBookmarkChanged"
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
