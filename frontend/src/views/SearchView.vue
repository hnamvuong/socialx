<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { RouterLink, useRoute, useRouter } from 'vue-router'

import MainLayout from '@/layouts/MainLayout.vue'

import PostCard from '@/components/post/PostCard.vue'

import { searchHashtags, searchPosts, searchUsers } from '@/services/searchService'

import type { SearchHashtag, SearchUser } from '@/services/searchService'

import type { Post, PostBookmarkState, PostLikeState, PostRepostState } from '@/types/post'

type SearchTab = 'users' | 'posts' | 'hashtags'

const route = useRoute()

const router = useRouter()

const activeTab = ref<SearchTab>('users')

const inputQuery = ref('')

const users = ref<SearchUser[]>([])

const posts = ref<Post[]>([])

const hashtags = ref<SearchHashtag[]>([])

const loading = ref(false)

const loadingMore = ref(false)

const searchError = ref<string | null>(null)

const nextCursor = ref<string | null>(null)

const hasMore = ref(false)

const sentinel = ref<HTMLElement | null>(null)

let observer: IntersectionObserver | null = null

const query = computed((): string => {
  const value = route.query.q

  if (typeof value !== 'string') {
    return ''
  }

  return value.trim()
})

function submitSearch(): void {
  const value = inputQuery.value.trim()

  if (!value) {
    return
  }

  void router.push({
    name: 'search',

    query: {
      q: value,
    },
  })
}

function disconnectObserver(): void {
  observer?.disconnect()

  observer = null
}

async function loadUsers(): Promise<void> {
  if (!query.value) {
    users.value = []

    return
  }

  loading.value = true
  searchError.value = null

  try {
    const response = await searchUsers(query.value)

    users.value = response.data.users
  } catch {
    users.value = []

    searchError.value = 'Không thể tìm kiếm người dùng.'
  } finally {
    loading.value = false
  }
}

async function loadHashtags(): Promise<void> {
  if (!query.value) {
    hashtags.value = []

    return
  }

  loading.value = true
  searchError.value = null

  try {
    const response = await searchHashtags(query.value)

    hashtags.value = response.data.hashtags
  } catch {
    hashtags.value = []

    searchError.value = 'Không thể tìm kiếm hashtag.'
  } finally {
    loading.value = false
  }
}

async function loadPosts(): Promise<void> {
  if (!query.value) {
    posts.value = []
    nextCursor.value = null
    hasMore.value = false

    return
  }

  loading.value = true
  searchError.value = null

  try {
    const response = await searchPosts(query.value)

    posts.value = response.data.posts

    nextCursor.value = response.data.pagination.next_cursor

    hasMore.value = response.data.pagination.has_more
  } catch {
    posts.value = []

    nextCursor.value = null
    hasMore.value = false

    searchError.value = 'Không thể tìm kiếm bài viết.'
  } finally {
    loading.value = false
  }
}

async function loadMorePosts(): Promise<void> {
  if (
    loadingMore.value ||
    !hasMore.value ||
    !nextCursor.value ||
    !query.value ||
    activeTab.value !== 'posts'
  ) {
    return
  }

  loadingMore.value = true

  searchError.value = null

  try {
    const response = await searchPosts(query.value, nextCursor.value)

    posts.value.push(...response.data.posts)

    nextCursor.value = response.data.pagination.next_cursor

    hasMore.value = response.data.pagination.has_more

    await nextTick()

    if (hasMore.value && nextCursor.value && sentinel.value && activeTab.value === 'posts') {
      const rect = sentinel.value.getBoundingClientRect()

      const nearViewport = rect.top < window.innerHeight + 300

      if (nearViewport) {
        void loadMorePosts()
      }
    }
  } catch {
    searchError.value = 'Không thể tải thêm bài viết.'
  } finally {
    loadingMore.value = false
  }
}

async function setupObserver(): Promise<void> {
  disconnectObserver()

  await nextTick()

  if (!sentinel.value || activeTab.value !== 'posts' || !hasMore.value) {
    return
  }

  observer = new IntersectionObserver(
    (entries) => {
      const entry = entries[0]

      if (!entry || !entry.isIntersecting) {
        return
      }

      void loadMorePosts()
    },
    {
      rootMargin: '300px 0px',
    },
  )

  observer.observe(sentinel.value)
}

async function loadActiveTab(): Promise<void> {
  disconnectObserver()

  searchError.value = null

  if (!query.value) {
    return
  }

  if (activeTab.value === 'users') {
    await loadUsers()

    return
  }

  if (activeTab.value === 'posts') {
    await loadPosts()

    await setupObserver()

    return
  }

  await loadHashtags()
}

function handlePostLikeChanged(state: PostLikeState): void {
  const post = posts.value.find((item) => item.id === state.postId)

  if (!post) {
    return
  }

  post.liked_by_me = state.liked

  post.likes_count = state.likesCount
}

function handlePostRepostChanged(state: PostRepostState): void {
  const post = posts.value.find((item) => item.id === state.postId)

  if (!post) {
    return
  }

  post.reposted_by_me = state.reposted

  post.reposts_count = state.repostsCount
}

function handlePostBookmarkChanged(state: PostBookmarkState): void {
  const post = posts.value.find((item) => item.id === state.postId)

  if (!post) {
    return
  }

  post.bookmarked_by_me = state.bookmarked
}

watch(
  activeTab,

  async () => {
    await loadActiveTab()
  },
)

watch(
  query,

  async (newQuery) => {
    disconnectObserver()

    inputQuery.value = newQuery

    users.value = []
    posts.value = []
    hashtags.value = []

    nextCursor.value = null
    hasMore.value = false

    searchError.value = null

    await loadActiveTab()
  },
)

onMounted(async () => {
  inputQuery.value = query.value

  await loadActiveTab()
})

onBeforeUnmount(() => {
  disconnectObserver()
})
</script>

<template>
  <MainLayout>
    <section class="search-view">
      <!-- HEADER -->
      <header class="search-view__header">
        <h1 class="search-view__title">Tìm kiếm</h1>

        <form class="search-view__form" @submit.prevent="submitSearch">
          <span class="search-view__search-icon" aria-hidden="true">
            <svg
              viewBox="0 0 24 24"
              width="18"
              height="18"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="11" cy="11" r="7" />

              <path d="m20 20-3.5-3.5" />
            </svg>
          </span>

          <input
            v-model="inputQuery"
            class="search-view__input"
            type="search"
            placeholder="Tìm kiếm trên SocialX"
            autocomplete="off"
            aria-label="Tìm kiếm trên SocialX"
          />
        </form>
      </header>

      <!-- EMPTY QUERY -->
      <div v-if="!query" class="search-view__welcome">
        <div class="search-view__welcome-icon" aria-hidden="true">
          <svg
            viewBox="0 0 24 24"
            width="32"
            height="32"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="11" cy="11" r="7" />

            <path d="m20 20-3.5-3.5" />
          </svg>
        </div>

        <strong class="search-view__welcome-title"> Tìm kiếm trên SocialX </strong>

        <span class="search-view__welcome-description"> Tìm người dùng, bài viết và hashtag. </span>
      </div>

      <template v-else>
        <!-- TABS -->
        <nav class="search-view__tabs" aria-label="Kết quả tìm kiếm">
          <button
            class="search-view__tab"
            :class="{
              'search-view__tab--active': activeTab === 'users',
            }"
            type="button"
            @click="activeTab = 'users'"
          >
            <span> Người dùng </span>
          </button>

          <button
            class="search-view__tab"
            :class="{
              'search-view__tab--active': activeTab === 'posts',
            }"
            type="button"
            @click="activeTab = 'posts'"
          >
            <span> Bài viết </span>
          </button>

          <button
            class="search-view__tab"
            :class="{
              'search-view__tab--active': activeTab === 'hashtags',
            }"
            type="button"
            @click="activeTab = 'hashtags'"
          >
            <span> Hashtag </span>
          </button>
        </nav>

        <!-- LOADING -->
        <div v-if="loading" class="search-view__status">
          <span class="search-view__spinner" aria-hidden="true" />

          <span> Đang tìm kiếm... </span>
        </div>

        <!-- ERROR -->
        <div v-else-if="searchError" class="search-view__status">
          {{ searchError }}
        </div>

        <!-- USERS -->
        <div v-else-if="activeTab === 'users'" class="search-view__results">
          <div v-if="users.length === 0" class="search-view__empty">
            <strong> Không tìm thấy người dùng </strong>

            <span> Thử tìm kiếm bằng tên hoặc username khác. </span>
          </div>

          <template v-else>
            <RouterLink
              v-for="user in users"
              :key="user.id"
              class="search-view__user"
              :to="`/@${encodeURIComponent(user.username)}`"
            >
              <div class="search-view__user-avatar" aria-hidden="true">
                {{
                  user.display_name?.charAt(0).toUpperCase() ||
                  user.username.charAt(0).toUpperCase()
                }}
              </div>

              <div class="search-view__user-info">
                <strong class="search-view__user-name">
                  {{ user.display_name }}
                </strong>

                <span class="search-view__username"> @{{ user.username }} </span>
              </div>
            </RouterLink>
          </template>
        </div>

        <!-- POSTS -->
        <div v-else-if="activeTab === 'posts'" class="search-view__posts">
          <div v-if="posts.length === 0" class="search-view__empty">
            <strong> Không tìm thấy bài viết </strong>

            <span> Thử tìm kiếm bằng từ khóa khác. </span>
          </div>

          <template v-else>
            <PostCard
              v-for="post in posts"
              :key="post.id"
              :post="post"
              @like-changed="handlePostLikeChanged"
              @repost-changed="handlePostRepostChanged"
              @bookmark-changed="handlePostBookmarkChanged"
            />
          </template>

          <div v-if="loadingMore" class="search-view__loading-more">
            <span class="search-view__spinner" aria-hidden="true" />
          </div>

          <div v-if="hasMore" ref="sentinel" class="search-view__sentinel" aria-hidden="true" />
        </div>

        <!-- HASHTAGS -->
        <div v-else class="search-view__results">
          <div v-if="hashtags.length === 0" class="search-view__empty">
            <strong> Không tìm thấy hashtag </strong>

            <span> Thử tìm kiếm bằng từ khóa khác. </span>
          </div>

          <template v-else>
            <RouterLink
              v-for="hashtag in hashtags"
              :key="hashtag.id"
              class="search-view__hashtag"
              :to="`/hashtag/${encodeURIComponent(hashtag.name)}`"
            >
              <span class="search-view__hashtag-icon"> # </span>

              <div class="search-view__hashtag-info">
                <strong> #{{ hashtag.name }} </strong>

                <span> Xem các bài viết </span>
              </div>
            </RouterLink>
          </template>
        </div>
      </template>
    </section>
  </MainLayout>
</template>

<style lang="scss" src="@/assets/styles/views/SearchView.scss"></style>
