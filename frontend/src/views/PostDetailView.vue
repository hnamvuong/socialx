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
  getPost,
} from '@/services/postService'
import type {
  Post,
} from '@/types/post'

const route = useRoute()

const router = useRouter()

const post = ref<Post | null>(null)

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

async function loadPost(): Promise<void> {
  const postId = getPostId()

  loading.value = true
  notFound.value = false
  errorMessage.value = null
  post.value = null

  if (!postId) {
    notFound.value = true
    loading.value = false
    return
  }

  try {
    const response = await getPost(postId)

    post.value = response.data.post
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      notFound.value = true
      return
    }

    errorMessage.value =
      'Không thể tải bài viết.'
  } finally {
    loading.value = false
  }
}

function handlePostUpdated(
  updatedPost: Post,
): void {
  post.value = updatedPost
}

function handlePostDeleted(): void {
  void router.replace('/')
}

function goBack(): void {
  router.back()
}

watch(
  () => route.params.id,
  () => {
    void loadPost()
  },
  {
    immediate: true,
  },
)
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

      <PostCard
        v-else-if="post"
        :post="post"
        @updated="handlePostUpdated"
        @deleted="handlePostDeleted"
      />

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
