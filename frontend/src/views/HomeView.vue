<script setup lang="ts">
import {
  onMounted,
  ref
} from 'vue'
import MainLayout from '@/layouts/MainLayout.vue'
import ThemeSwitcher from '@/components/theme/ThemeSwitcher.vue'
import PostComposer from '@/components/post/PostComposer.vue'
import PostCard from '@/components/post/PostCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  Post,
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
} from '@/types/post'

import {
  getFollowingFeed,
} from '@/services/postService'

const authStore = useAuthStore()

const posts = ref<Post[]>([])

const loadingFeed = ref(false)

const loadingMore = ref(false)

const feedError = ref<string | null>(null)

const currentPage = ref(1)

const hasMore = ref(false)

function handlePostUpdated(
  updatedPost: Post,
): void {
  const index =
    posts.value
      .findIndex(
        (post) =>
          post.id ===
          updatedPost.id,
      )

  if (index === -1) {
    return
  }

  posts.value[index] =
    updatedPost
}

function handlePostDeleted(
  postId: number,
): void {
  posts.value =
    posts.value.filter(
      (post) =>
        post.id !== postId,
    )
}

function handlePostLikeChanged(
  state: PostLikeState,
): void {
  const post =
    posts.value.find(
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
    posts.value.find(
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

function handlePostBookmarkChanged(
  state: PostBookmarkState,
): void {
  const post =
    posts.value.find(
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

async function loadFollowingFeed(): Promise<void> {
  if (
    !authStore.isAuthenticated
  ) {
    posts.value = []
    loadingFeed.value = false

    return
  }

  loadingFeed.value = true
  feedError.value = null

  try {
    const response =
      await getFollowingFeed(1)

    posts.value =
      response.data.posts

    currentPage.value =
      response
        .data
        .pagination
        .current_page

    hasMore.value =
      response
        .data
        .pagination
        .has_more
  } catch {
    posts.value = []

    feedError.value =
      'Không thể tải Following Feed.'
  } finally {
    loadingFeed.value =
      false
  }
}

async function loadMore(): Promise<void> {
  if (
    loadingMore.value
    || !hasMore.value
  ) {
    return
  }

  loadingMore.value = true

  try {
    const response =
      await getFollowingFeed(
        currentPage.value + 1,
      )

    posts.value.push(
      ...response.data.posts,
    )

    currentPage.value =
      response
        .data
        .pagination
        .current_page

    hasMore.value =
      response
        .data
        .pagination
        .has_more
  } catch {
    feedError.value =
      'Không thể tải thêm bài viết.'
  } finally {
    loadingMore.value =
      false
  }
}

onMounted(() => {
  void loadFollowingFeed()
})
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
      />

      <div
        v-else
        class="home-auth-notice"
      >
        Đăng nhập để tạo bài viết.
      </div>

      <div
        v-if="loadingFeed"
        class="home-feed-loading"
      >
        <AppSkeleton
          width="100%"
          height="140px"
        />

        <AppSkeleton
          width="100%"
          height="140px"
        />

        <AppSkeleton
          width="100%"
          height="140px"
        />
      </div>

      <div
        v-else-if="feedError && posts.length === 0"
        class="feed-placeholder"
      >
        <h2 class="feed-placeholder__title">
          Không thể tải Feed
        </h2>

        <p class="feed-placeholder__description">
          {{ feedError }}
        </p>

        <AppButton
          variant="secondary"
          @click="loadFollowingFeed"
        >
          Thử lại
        </AppButton>
      </div>

      <section
        v-else-if="posts.length > 0"
        class="home-post-list"
      >
        <PostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @updated="handlePostUpdated"
          @deleted="handlePostDeleted"
          @like-changed="handlePostLikeChanged"
          @repost-changed="handlePostRepostChanged"
          @bookmark-changed="handlePostBookmarkChanged"
        />

        <div
          v-if="hasMore"
          class="home-feed-load-more"
        >
          <AppButton
            variant="secondary"
            :disabled="loadingMore"
            @click="loadMore"
          >
            {{
              loadingMore
                ? 'Đang tải...'
                : 'Xem thêm'
            }}
          </AppButton>
        </div>
      </section>

      <div
        v-else-if="authStore.isAuthenticated"
        class="feed-placeholder"
      >
        <h2 class="feed-placeholder__title">
          Following Feed đang trống
        </h2>

        <p class="feed-placeholder__description">
          Hãy theo dõi người dùng để xem bài viết của họ tại đây.
        </p>
      </div>

      <div
        v-else
        class="feed-placeholder"
      >
        <h2 class="feed-placeholder__title">
          Đăng nhập để xem Feed
        </h2>

        <p class="feed-placeholder__description">
          Following Feed dành cho người dùng đã đăng nhập.
        </p>
      </div>
    </section>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/HomeView.scss"
></style>
