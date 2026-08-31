<script setup lang="ts">
import {
  computed,
  ref,
} from 'vue'
import {
  RouterLink,
} from 'vue-router'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppDropdown from '@/components/ui/AppDropdown.vue'
import EditPostModal from '@/components/post/EditPostModal.vue'
import PostActions from '@/components/post/PostActions.vue'
import PostMediaGrid from '@/components/post/PostMediaGrid.vue'
import QuotedPostCard from '@/components/post/QuotedPostCard.vue'
import QuotePostModal from '@/components/post/QuotePostModal.vue'
import {
  useAuthStore,
} from '@/stores/auth'
import {
  useToastStore,
} from '@/stores/toast'
import {
  formatFullDateTime,
  formatPostTime,
} from '@/utils/date'
import type {
  Post,
  PostLikeState,
  PostRepostState,
} from '@/types/post'
import axios from 'axios'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import {
  deletePost,
  likePost,
  repostPost,
  unlikePost,
  unrepostPost,
} from '@/services/postService'

const props = defineProps<{
  post: Post
}>()

const emit = defineEmits<{
  updated: [post: Post]
  deleted: [postId: number]
  reply: [post: Post]
  likeChanged: [state: PostLikeState]
  repostChanged: [state: PostRepostState]
  quoteCreated: [post: Post]
}>()

const authStore = useAuthStore()

const toastStore = useToastStore()

const editOpen = ref(false)

const profilePath =
  computed(() => {
    return `/@${props.post.user.username}`
  })

const formattedTime =
  computed(() => {
    return formatPostTime(
      props.post.created_at,
    )
  })

const fullDateTime =
  computed(() => {
    return formatFullDateTime(
      props.post.created_at,
    )
  })

const isOwnPost =
  computed(() => {
    return (
      !!authStore.user
      && authStore.user.id
        === props.post.user.id
    )
  })

function handleUpdated(
  post: Post,
): void {
  editOpen.value = false

  emit(
    'updated',
    post,
  )

  toastStore.success(
    'Đã cập nhật bài viết.',
  )
}

const wasEdited =
  computed(() => {
    return (
      props.post.updated_at
      !== props.post.created_at
    )
  })

const deleteConfirmOpen = ref(false)

const deleting = ref(false)

async function handleDelete(): Promise<void> {
  if (deleting.value) {
    return
  }

  deleting.value = true

  try {
    await deletePost(
      props.post.id,
    )

    deleteConfirmOpen.value =
      false

    emit(
      'deleted',
      props.post.id,
    )

    toastStore.success(
      'Đã xóa bài viết.',
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 403
    ) {
      toastStore.error(
        'Bạn không có quyền xóa bài viết này.',
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      emit(
        'deleted',
        props.post.id,
      )

      toastStore.error(
        'Bài viết không còn tồn tại.',
      )

      return
    }

    toastStore.error(
      'Không thể xóa bài viết.',
    )
  } finally {
    deleting.value = false
  }
}

const postPath =
  computed(() => {
    return `/post/${props.post.id}`
  })

const liking = ref(false)

async function handleLike(): Promise<void> {
  if (liking.value) {
    return
  }

  if (!authStore.isAuthenticated) {
    toastStore.error(
      'Bạn cần đăng nhập để thích bài viết.',
    )

    return
  }

  const previousLiked =
    props.post.liked_by_me

  const previousCount =
    props.post.likes_count

  const optimisticLiked =
    !previousLiked

  const optimisticCount =
    optimisticLiked
      ? previousCount + 1
      : Math.max(
          0,
          previousCount - 1,
        )

  liking.value = true

  emit(
    'likeChanged',
    {
      postId:
        props.post.id,

      liked:
        optimisticLiked,

      likesCount:
        optimisticCount,
    },
  )

  try {
    const response =
      optimisticLiked
        ? await likePost(
            props.post.id,
          )
        : await unlikePost(
            props.post.id,
          )

    emit(
      'likeChanged',
      {
        postId:
          props.post.id,

        liked:
          response.data.liked,

        likesCount:
          response.data.likes_count,
      },
    )
  } catch (error: unknown) {
    emit(
      'likeChanged',
      {
        postId:
          props.post.id,

        liked:
          previousLiked,

        likesCount:
          previousCount,
      },
    )

    if (
      axios.isAxiosError(error)
      && error.response?.status === 401
    ) {
      toastStore.error(
        'Phiên đăng nhập không còn hợp lệ.',
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      toastStore.error(
        'Bài viết không còn tồn tại.',
      )

      return
    }

    toastStore.error(
      optimisticLiked
        ? 'Không thể thích bài viết.'
        : 'Không thể bỏ thích bài viết.',
    )
  } finally {
    liking.value = false
  }
}

const reposting = ref(false)

const quoteOpen = ref(false)

async function handleRepost(): Promise<void> {
  if (reposting.value) {
    return
  }

  if (!authStore.isAuthenticated) {
    toastStore.error(
      'Bạn cần đăng nhập để đăng lại bài viết.',
    )

    return
  }

  const previousReposted = props.post.reposted_by_me

  const previousCount = props.post.reposts_count

  const optimisticReposted = !previousReposted

  const optimisticCount =
    optimisticReposted
      ? previousCount + 1
      : Math.max(
          0,
          previousCount - 1,
        )

  reposting.value = true

  emit(
    'repostChanged',
    {
      postId:
        props.post.id,

      reposted:
        optimisticReposted,

      repostsCount:
        optimisticCount,
    },
  )

  try {
    const response =
      optimisticReposted
        ? await repostPost(
            props.post.id,
          )
        : await unrepostPost(
            props.post.id,
          )

    emit(
      'repostChanged',
      {
        postId:
          props.post.id,

        reposted:
          response.data.reposted,

        repostsCount:
          response.data.reposts_count,
      },
    )
  } catch (error: unknown) {
    emit(
      'repostChanged',
      {
        postId:
          props.post.id,

        reposted:
          previousReposted,

        repostsCount:
          previousCount,
      },
    )

    if (
      axios.isAxiosError(error)
      && error.response?.status === 401
    ) {
      toastStore.error(
        'Phiên đăng nhập không còn hợp lệ.',
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      toastStore.error(
        'Bài viết không còn tồn tại.',
      )

      return
    }

    toastStore.error(
      optimisticReposted
        ? 'Không thể đăng lại bài viết.'
        : 'Không thể hoàn tác đăng lại.',
    )
  } finally {
    reposting.value = false
  }
}

function handleQuote(): void {
  if (!authStore.isAuthenticated) {
    toastStore.error(
      'Bạn cần đăng nhập để trích dẫn bài viết.',
    )

    return
  }

  quoteOpen.value = true
}

function closeQuote(): void {
  quoteOpen.value = false
}

function handleQuoteCreated(
  post: Post,
): void {
  emit(
    'quoteCreated',
    post,
  )
}
</script>

<template>
  <article class="post-card">
    <div class="post-card__avatar">
      <RouterLink
        :to="profilePath"
        :aria-label="
          `Xem hồ sơ ${post.user.display_name}`
        "
      >
        <AppAvatar
          :src="
            post.user.avatar_url
          "
          :name="
            post.user.display_name
          "
          :size="44"
        />
      </RouterLink>
    </div>

    <div class="post-card__main">
      <header class="post-card__header">
        <div class="post-card__identity">
          <RouterLink
            :to="profilePath"
            class="post-card__display-name"
          >
            {{ post.user.display_name }}
          </RouterLink>

          <span
            v-if="post.user.is_verified"
            class="post-card__verified"
            title="Tài khoản đã xác minh"
            aria-label="Tài khoản đã xác minh"
          >
            ✓
          </span>

          <RouterLink
            :to="profilePath"
            class="post-card__username"
          >
            @{{ post.user.username }}
          </RouterLink>

          <span
            class="post-card__separator"
            aria-hidden="true"
          >
            ·
          </span>

          <RouterLink
            :to="postPath"
            class="post-card__time-link"
          >
            <time
              :datetime="post.created_at"
              :title="fullDateTime"
              class="post-card__time"
            >
              {{ formattedTime }}
            </time>
          </RouterLink>

          <span
            v-if="wasEdited"
            class="post-card__edited"
          >
            · đã chỉnh sửa
          </span>
        </div>

        <AppDropdown
          v-if="isOwnPost"
        >
          <template #trigger>
            <AppButton
              variant="ghost"
              size="sm"
              class="post-card__menu-trigger"
              aria-label="Tùy chọn bài viết"
            >
              •••
            </AppButton>
          </template>

          <button
            type="button"
            class="post-card__menu-item"
            @click="editOpen = true"
          >
            Chỉnh sửa
          </button>

          <button
            type="button"
            class="
              post-card__menu-item
              post-card__menu-item--danger
            "
            @click="deleteConfirmOpen = true"
          >
            Xóa
          </button>
        </AppDropdown>
      </header>

      <RouterLink
        :to="postPath"
        class="post-card__detail-link"
      >
        <p
          v-if="post.content"
          class="post-card__content"
        >
          {{ post.content }}
        </p>

        <PostMediaGrid
          :media="post.media"
        />

        <QuotedPostCard
          v-if="post.quoted_post"
          :post="post.quoted_post"
        />
      </RouterLink>

      <PostActions
        :liked="post.liked_by_me"
        :likes-count="post.likes_count"
        :like-disabled="liking"
        :reposted="post.reposted_by_me"
        :reposts-count="post.reposts_count"
        :repost-disabled="reposting"
        @reply="
          $emit(
            'reply',
            post
          )
        "
        @like="handleLike"
        @repost="handleRepost"
        @quote="handleQuote"
      />
    </div>

    <EditPostModal
      v-if="isOwnPost"
      :open="editOpen"
      :post="post"
      @close="editOpen = false"
      @updated="handleUpdated"
    />
    <ConfirmDialog
      v-if="isOwnPost"
      :open="deleteConfirmOpen"
      title="Xóa bài viết?"
      message="Bài viết này sẽ bị xóa vĩnh viễn."
      confirm-text="Xóa"
      cancel-text="Hủy"
      danger
      :loading="deleting"
      @cancel="deleteConfirmOpen = false"
      @confirm="handleDelete"
    />

    <QuotePostModal
      :open="quoteOpen"
      :quoted-post="post"
      @close="closeQuote"
      @created="
        handleQuoteCreated
      "
    />
  </article>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/PostCard.scss"
></style>
