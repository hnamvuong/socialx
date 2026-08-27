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
} from '@/types/post'

const props = defineProps<{
  post: Post
}>()

const emit = defineEmits<{
  updated: [post: Post]
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

          <time
            :datetime="
              post.created_at
            "
            :title="
              fullDateTime
            "
            class="post-card__time"
          >
            {{ formattedTime }}
          </time>

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
        </AppDropdown>
      </header>

      <p
        v-if="post.content"
        class="post-card__content"
      >
        {{ post.content }}
      </p>

      <PostMediaGrid
        :media="post.media"
      />

      <PostActions />
    </div>

    <EditPostModal
      v-if="isOwnPost"
      :open="editOpen"
      :post="post"
      @close="editOpen = false"
      @updated="handleUpdated"
    />
  </article>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/PostCard.scss"
></style>
