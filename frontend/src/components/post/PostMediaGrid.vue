<script setup lang="ts">
import type {
  PostMedia,
} from '@/types/post'

defineProps<{
  media: PostMedia[]
}>()
</script>

<template>
  <div
    v-if="media.length > 0"
    class="post-media-grid"
    :class="
      `post-media-grid--${Math.min(
        media.length,
        4,
      )}`
    "
  >
    <div
      v-for="item in media"
      :key="item.id"
      class="post-media-grid__item"
    >
      <img
        v-if="item.type === 'image'"
        :src="item.url"
        alt=""
        class="post-media-grid__image"
        loading="lazy"
      >
    </div>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/PostMediaGrid.scss"
></style>
