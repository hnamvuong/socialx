<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import AppAvatar from '@/components/ui/AppAvatar.vue'
import PostMediaGrid from '@/components/post/PostMediaGrid.vue'

import type {
  QuotedPost,
} from '@/types/post'

const props = defineProps<{
  post: QuotedPost
}>()

const postPath =
  computed(() => {
    return `/post/${props.post.id}`
  })

const profilePath =
  computed(() => {
    return `/@${props.post.user.username}`
  })
</script>

<template>
  <article class="quoted-post-card">
    <div class="quoted-post-card__author">
      <RouterLink
        :to="profilePath"
        class="quoted-post-card__avatar-link"
        @click.stop
      >
        <AppAvatar
          :src="post.user.avatar_url"
          :name="post.user.display_name"
          :size="24"
        />
      </RouterLink>

      <RouterLink
        :to="profilePath"
        class="quoted-post-card__author-name"
        @click.stop
      >
        <strong>
          {{ post.user.display_name }}
        </strong>

        <span
          v-if="post.user.is_verified"
          class="quoted-post-card__verified"
          aria-label="Đã xác minh"
        >
          ✓
        </span>

        <span class="quoted-post-card__username">
          @{{ post.user.username }}
        </span>
      </RouterLink>
    </div>

    <RouterLink
      :to="postPath"
      class="quoted-post-card__content-link"
      @click.stop
    >
      <p
        v-if="post.content"
        class="quoted-post-card__content"
      >
        {{ post.content }}
      </p>

      <PostMediaGrid
        v-if="post.media.length > 0"
        :media="post.media"
      />
    </RouterLink>
  </article>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/QuotedPostCard.scss"
></style>
