<script setup lang="ts">
import {
  ref,
  watch,
} from 'vue'
import axios from 'axios'
import {
  useRoute,
  useRouter,
} from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'
import PostCard from '@/components/post/PostCard.vue'
import {
  getThread,
} from '@/services/postService'
import ReplyComposer from '@/components/post/ReplyComposer.vue'
import ThreadList from '@/components/post/ThreadList.vue'
import {
  useAuthStore,
} from '@/stores/auth'

import type {
  Post,
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
} from '@/types/post'

const route = useRoute()

const router = useRouter()

const authStore = useAuthStore()

const rootPost = ref<Post | null>(null)

const replies = ref<Post[]>([])

const loading = ref(true)

const notFound = ref(false)

const errorMessage = ref<string | null>(null)

function getPostId(): number | null {
  const rawId = route.params.id

  const value =
    Array.isArray(rawId)
      ? rawId[0]
      : rawId

  if (!value) return null

  const postId = Number(value)

  if (
    !Number.isInteger(postId)
    || postId <= 0
  ) {
    return null
  }

  return postId
}

async function loadThread(): Promise<void> {
  const postId =
    getPostId()

  loading.value = true
  notFound.value = false
  errorMessage.value = null

  rootPost.value = null
  replies.value = []

  if (!postId) {
    notFound.value = true
    loading.value = false
    return
  }

  try {
    const response =
      await getThread(
        postId
      )

    rootPost.value =
      response.data.root

    replyTarget.value =
      response.data.root

    replies.value =
      response.data.replies
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      notFound.value = true
      return
    }

    errorMessage.value =
      'Không thể tải cuộc hội thoại.'
  } finally {
    loading.value = false
  }
}

function handlePostUpdated(
  updatedPost: Post,
): void {
  if (
    rootPost.value?.id
      === updatedPost.id
  ) {
    rootPost.value =
      updatedPost

    return
  }

  const index =
    replies.value.findIndex(
      (reply) =>
        reply.id ===
        updatedPost.id,
    )

  if (index !== -1) {
    replies.value[index] =
      updatedPost
  }
}

async function handlePostDeleted(
  postId: number,
): Promise<void> {
  if (
    rootPost.value?.id
      === postId
  ) {
    void router.replace('/')
    return
  }

  await loadThread()
}

function goBack(): void {
  router.back()
}

function handleReplyCreated(
  reply: Post,
): void {
  replies.value.push(
    reply,
  )

  if (rootPost.value) {
    replyTarget.value = rootPost.value
  }
}

const replyTarget = ref<Post | null>(null)

function handleReplyRequested(
  post: Post,
): void {
  replyTarget.value =
    post
}

watch(
  () => route.params.id,
  () => {
    void loadThread()
  },
  {
    immediate: true,
  },
)

function handlePostLikeChanged(
  state: PostLikeState,
): void {
  if (
    rootPost.value?.id ===
    state.postId
  ) {
    rootPost.value = {
      ...rootPost.value,

      liked_by_me:
        state.liked,

      likes_count:
        state.likesCount,
    }

    return
  }

  const index =
    replies.value.findIndex(
      (reply) =>
        reply.id ===
        state.postId,
    )

  if (index === -1) {
    return
  }

  const reply = replies.value[index]

  if (!reply) {
    return
  }

  replies.value[index] = {
    ...reply,

    liked_by_me: state.liked,

    likes_count: state.likesCount,
  }
}

function handlePostRepostChanged(
  state: PostRepostState,
): void {
  if (
    rootPost.value?.id
      === state.postId
  ) {
    rootPost.value = {
      ...rootPost.value,
      reposted_by_me: state.reposted,
      reposts_count: state.repostsCount,
    }

    return
  }

  const index =
    replies.value.findIndex(
      (reply) =>
        reply.id ===
        state.postId,
    )

  if (index === -1) {
    return
  }

  const reply = replies.value[index]

  if (!reply) {
    return
  }

  replies.value[index] = {
    ...reply,
    reposted_by_me: state.reposted,
    reposts_count: state.repostsCount,
  }
}

async function handleQuoteCreated(
  post: Post,
): Promise<void> {
  await router.push(
    `/post/${post.id}`
  )
}

function handlePostBookmarkChanged(
  state: PostBookmarkState,
): void {
  if (
    rootPost.value?.id ===
    state.postId
  ) {
    rootPost.value = {
      ...rootPost.value,

      bookmarked_by_me:
        state.bookmarked,
    }

    return
  }

  const index =
    replies.value.findIndex(
      (reply) =>
        reply.id ===
        state.postId,
    )

  if (index === -1) {
    return
  }

  const reply =
    replies.value[index]

  if (!reply) {
    return
  }

  replies.value[index] = {
    ...reply,

    bookmarked_by_me:
      state.bookmarked,
  }
}
</script>

<template>
  <MainLayout>
    <section class="post-detail-view">
      <header class="post-detail-view__header">
        <AppButton
          variant="ghost"
          size="sm"
          aria-label="Quay lại"
          @click="goBack"
        >
          ←
        </AppButton>

        <h1 class="post-detail-view__title">
          Bài viết
        </h1>
      </header>

      <div
        v-if="loading"
        class="post-detail-view__loading"
      >
        <div class="post-detail-view__loading-row">
          <AppSkeleton
            width="44px"
            height="44px"
            radius="50%"
          />

          <div class="post-detail-view__loading-main">
            <AppSkeleton
              width="180px"
              height="18px"
            />

            <AppSkeleton
              width="100%"
              height="18px"
            />

            <AppSkeleton
              width="75%"
              height="18px"
            />
          </div>
        </div>
      </div>

      <template
        v-else-if="rootPost"
      >
        <PostCard
          :post="rootPost"
          @updated="handlePostUpdated"
          @deleted="handlePostDeleted"
          @reply="handleReplyRequested"
          @like-changed="handlePostLikeChanged"
          @repost-changed="handlePostRepostChanged"
          @quote-created="handleQuoteCreated"
          @bookmark-changed="handlePostBookmarkChanged"
        />

        <ReplyComposer
          v-if="
            authStore.isAuthenticated
            && replyTarget
          "
          :parent-post="replyTarget"
          @created="handleReplyCreated"
        />

        <div
          v-else
          class="post-detail-view__reply-notice"
        >
          Đăng nhập để tham gia cuộc hội thoại.
        </div>

        <ThreadList
          :root="rootPost"
          :replies="replies"
          @updated="handlePostUpdated"
          @deleted="handlePostDeleted"
          @reply="handleReplyRequested"
          @like-changed="handlePostLikeChanged"
          @repost-changed="handlePostRepostChanged"
          @quote-created="handleQuoteCreated"
          @bookmark-changed="handlePostBookmarkChanged"
        />
      </template>

      <div
        v-else-if="notFound"
        class="post-detail-view__state"
      >
        <h2 class="post-detail-view__state-title">
          Không tìm thấy bài viết
        </h2>

        <p class="post-detail-view__state-description">
          Bài viết có thể đã bị xóa hoặc không còn tồn tại.
        </p>
      </div>

      <div
        v-else
        class="post-detail-view__state"
      >
        <h2 class="post-detail-view__state-title">
          Đã xảy ra lỗi
        </h2>

        <p class="post-detail-view__state-description">
          {{
            errorMessage
            ?? 'Không thể tải bài viết.'
          }}
        </p>

        <AppButton
          variant="secondary"
          @click="loadPost"
        >
          Thử lại
        </AppButton>
      </div>
    </section>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/PostDetailView.scss"
></style>
