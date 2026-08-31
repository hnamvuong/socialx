<script setup lang="ts">
import {
  onMounted,
  ref,
} from 'vue'
import {
  useRouter,
} from 'vue-router'
import axios from 'axios'
import MainLayout from '@/layouts/MainLayout.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'
import PostCard from '@/components/post/PostCard.vue'

import {
  getBookmarks,
} from '@/services/bookmarkService'

import type {
  PaginationMeta,
  Post,
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
} from '@/types/post'

const router = useRouter()

const posts =
  ref<Post[]>([])

const pagination =
  ref<PaginationMeta | null>(
    null,
  )

const loading = ref(true)

const loadingMore = ref(false)

const errorMessage = ref<string | null>(null)

async function loadBookmarks(): Promise<void> {
  loading.value = true
  errorMessage.value = null

  try {
    const response =
      await getBookmarks(1)

    posts.value =
      response.data.posts

    pagination.value =
      response.data.pagination
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 401
    ) {
      errorMessage.value =
        'Bạn cần đăng nhập để xem bài viết đã lưu.'
    } else {
      errorMessage.value =
        'Không thể tải danh sách bài viết đã lưu.'
    }
  } finally {
    loading.value = false
  }
}

async function loadMore(): Promise<void> {
  if (
    loadingMore.value
    || !pagination.value
    || !pagination.value.has_more
  ) {
    return
  }

  loadingMore.value = true

  try {
    const nextPage =
      pagination.value.current_page + 1

    const response =
      await getBookmarks(
        nextPage,
      )

    posts.value.push(
      ...response.data.posts,
    )

    pagination.value =
      response.data.pagination
  } catch {
    errorMessage.value =
      'Không thể tải thêm bài viết.'
  } finally {
    loadingMore.value = false
  }
}

function replacePost(
  updatedPost: Post,
): void {
  const index =
    posts.value.findIndex(
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

function removePost(
  postId: number,
): void {
  posts.value =
    posts.value.filter(
      (post) =>
        post.id !== postId,
    )
}

function handleLikeChanged(
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

function handleRepostChanged(
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

function handleBookmarkChanged(
  state: PostBookmarkState,
): void {
  const post =
    posts.value.find(
      (item) =>
        item.id === state.postId,
    )

  if (post) {
    post.bookmarked_by_me =
      state.bookmarked
  }

  if (
    state.phase === 'confirmed'
    && !state.bookmarked
  ) {
    removePost(
      state.postId,
    )

    if (pagination.value) {
      pagination.value = {
        ...pagination.value,

        total:
          Math.max(
            0,
            pagination.value.total - 1,
          ),
      }
    }
  }
}

async function handleQuoteCreated(
  post: Post,
): Promise<void> {
  await router.push(
    `/post/${post.id}`
  )
}

function handleReply(
  post: Post
): void {
  void router.push(
    `/post/${post.id}`
  )
}

onMounted(() => {
  void loadBookmarks()
})
</script>

<template>
  <MainLayout>
    <main class="bookmark-view">
      <header class="bookmark-view__header">
        <h1>
          Dấu trang
        </h1>
      </header>

      <div
        v-if="loading"
        class="bookmark-view__loading"
      >
        <AppSkeleton
          v-for="index in 4"
          :key="index"
          :height="140"
        />
      </div>

      <section
        v-else-if="errorMessage"
        class="bookmark-view__state"
      >
        <p>
          {{ errorMessage }}
        </p>

        <AppButton
          variant="secondary"
          @click="loadBookmarks"
        >
          Thử lại
        </AppButton>
      </section>

      <section
        v-else-if="posts.length === 0"
        class="bookmark-view__state"
      >
        <h2>
          Chưa có bài viết đã lưu
        </h2>

        <p>
          Khi bạn lưu một bài viết,
          nó sẽ xuất hiện ở đây.
        </p>
      </section>

      <template v-else>
        <PostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @updated="replacePost"
          @deleted="removePost"
          @like-changed="handleLikeChanged"
          @repost-changed="handleRepostChanged"
          @bookmark-changed="handleBookmarkChanged"
          @quote-created="handleQuoteCreated"
          @reply="handleReply"
        />

        <div
          v-if="
            pagination?.has_more
          "
          class="bookmark-view__load-more"
        >
          <AppButton
            variant="secondary"
            :loading="loadingMore"
            @click="loadMore"
          >
            Xem thêm
          </AppButton>
        </div>
      </template>
    </main>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/BookmarkView.scss"
></style>
