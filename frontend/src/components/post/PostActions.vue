<script setup lang="ts">
defineProps<{
  liked: boolean
  likesCount: number
  likeDisabled?: boolean
}>()

defineEmits<{
  reply: []
  like: []
}>()
</script>

<template>
  <div
    class="post-actions"
    aria-label="Hành động bài viết"
  >
    <button
      type="button"
      class="post-actions__item"
      @click="$emit('reply')"
      title="Trả lời"
    >
      <span
        class="post-actions__icon"
        aria-hidden="true"
      >
        ○
      </span>

      <span class="post-actions__label">
        Trả lời
      </span>
    </button>

    <button
      type="button"
      class="post-actions__item"
      disabled
      title="Đăng lại"
    >
      <span
        class="post-actions__icon"
        aria-hidden="true"
      >
        ↻
      </span>

      <span class="post-actions__label">
        Đăng lại
      </span>
    </button>

    <button
      type="button"
      class="post-actions__item"
      :class="{
        'post-actions__item--liked':
          liked,
      }"
      :disabled="likeDisabled"
      :aria-pressed="liked"
      :aria-label="
        liked
          ? 'Bỏ thích bài viết'
          : 'Thích bài viết'
      "
      @click="$emit('like')"
    >
      <span class="post-actions__icon">
        {{ liked ? '♥' : '♡' }}
      </span>

      <span
        v-if="likesCount > 0"
        class="post-actions__count"
      >
        {{ likesCount }}
      </span>

      <span
        v-else
        class="post-actions__label"
      >
        Thích
      </span>
    </button>

    <button
      type="button"
      class="post-actions__item"
      disabled
      title="Đánh dấu"
    >
      <span
        class="post-actions__icon"
        aria-hidden="true"
      >
        ◇
      </span>

      <span class="post-actions__label">
        Lưu
      </span>
    </button>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/PostActions.scss"
></style>
