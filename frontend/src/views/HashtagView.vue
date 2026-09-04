<script setup lang="ts">
import {
  nextTick,
  onBeforeUnmount,
  ref,
  watch,
} from 'vue'
import {
  useRoute,
} from 'vue-router'

import MainLayout from '@/layouts/MainLayout.vue'
import PostCard from '@/components/post/PostCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'
import ThemeSwitcher from '@/components/theme/ThemeSwitcher.vue'

import {
  getHashtagPosts,
} from '@/services/postService'

import type {
  Post,
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
} from '@/types/post'

const route =
  useRoute()

const hashtagName =
  ref('')

const posts =
  ref<Post[]>([])

const loading =
  ref(false)

const loadingMore =
  ref(false)

const error =
  ref<string | null>(
    null,
  )

const nextCursor =
  ref<string | null>(
    null,
  )

const hasMore =
  ref(false)

const loadMoreSentinel =
  ref<HTMLElement | null>(
    null,
  )

let loadMoreObserver:
  IntersectionObserver | null =
  null

  function handlePostUpdated(
  updatedPost: Post,
): void {
  const index =
    posts.value.findIndex(
      (post) =>
        post.id === updatedPost.id,
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
        item.id === state.postId,
    )

  if (!post) {
    return
  }

  post.liked_by_me =
    state.liked

  post.likes_count =
    state.likesCount
}

function handlePostRepostChanged(
  state: PostRepostState,
): void {
  const post =
    posts.value.find(
      (item) =>
        item.id === state.postId,
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
        item.id === state.postId,
    )

  if (!post) {
    return
  }

  post.bookmarked_by_me =
    state.bookmarked
}

async function loadHashtag(): Promise<void> {
  const hashtag =
    String(
      route.params.hashtag ?? '',
    )

  hashtagName.value =
    hashtag

  loading.value =
    true

  error.value =
    null

  posts.value =
    []

  nextCursor.value =
    null

  hasMore.value =
    false

  try {
    const response =
      await getHashtagPosts(
        hashtag,
      )

    hashtagName.value =
      response
        .data
        .hashtag
        .name

    posts.value =
      response.data.posts

    nextCursor.value =
      response
        .data
        .pagination
        .next_cursor

    hasMore.value =
      response
        .data
        .pagination
        .has_more
  } catch {
    error.value =
      'Không thể tải hashtag.'
  } finally {
    loading.value =
      false
  }
}

async function loadMore(): Promise<void> {
  if (
    loadingMore.value
    || !hasMore.value
    || !nextCursor.value
  ) {
    return
  }

  loadingMore.value =
    true

  try {
    const response =
      await getHashtagPosts(
        hashtagName.value,
        nextCursor.value,
      )

    posts.value.push(
      ...response.data.posts,
    )

    nextCursor.value =
      response
        .data
        .pagination
        .next_cursor

    hasMore.value =
      response
        .data
        .pagination
        .has_more
  } finally {
    loadingMore.value =
      false

    await nextTick()

    if (
      hasMore.value
      && nextCursor.value
      && isLoadMoreSentinelVisible()
    ) {
      void loadMore()
    }
  }
}

function isLoadMoreSentinelVisible(): boolean {
  const element =
    loadMoreSentinel.value

  if (!element) {
    return false
  }

  const rect =
    element
      .getBoundingClientRect()

  return (
    rect.top
      <= window.innerHeight + 300
    && rect.bottom >= -300
  )
}

function setupInfiniteScroll(): void {
  loadMoreObserver
    ?.disconnect()

  if (
    !loadMoreSentinel.value
  ) {
    return
  }

  loadMoreObserver =
    new IntersectionObserver(
      (entries) => {
        const entry =
          entries[0]

        if (
          !entry?.isIntersecting
        ) {
          return
        }

        void loadMore()
      },
      {
        root: null,
        rootMargin:
          '300px 0px',
        threshold: 0,
      },
    )

  loadMoreObserver.observe(
    loadMoreSentinel.value,
  )
}

watch(
  () =>
    route.params.hashtag,
  async () => {
    loadMoreObserver
      ?.disconnect()

    await loadHashtag()

    await nextTick()

    setupInfiniteScroll()
  },
  {
    immediate: true,
  },
)

onBeforeUnmount(() => {
  loadMoreObserver
    ?.disconnect()

  loadMoreObserver =
    null
})
</script>

<template>
  <MainLayout>
    <section class="hashtag-page">
      <header class="hashtag-page__header">
        <div>
          <h1 class="hashtag-page__title">
            #{{ hashtagName }}
          </h1>

          <p class="hashtag-page__subtitle">
            Bài viết
          </p>
        </div>

        <ThemeSwitcher />
      </header>

      <div
        v-if="loading"
        class="hashtag-page__loading"
      >
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
        v-else-if="error"
        class="hashtag-page__state"
      >
        <p>
          {{ error }}
        </p>

        <AppButton
          variant="secondary"
          @click="loadHashtag"
        >
          Thử lại
        </AppButton>
      </div>

      <div
        v-else-if="posts.length === 0"
        class="hashtag-page__state"
      >
        Chưa có bài viết nào với
        #{{ hashtagName }}.
      </div>

      <section
        v-else
        class="hashtag-page__posts"
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
          ref="loadMoreSentinel"
          class="hashtag-page__sentinel"
          aria-hidden="true"
        />

        <div
          v-if="loadingMore"
          class="hashtag-page__loading-more"
        >
          <AppSkeleton
            width="100%"
            height="140px"
          />
        </div>
      </section>
    </section>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/HashtagView.scss"
></style>
