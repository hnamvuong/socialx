<template>
  <div
    class="post-actions"
    aria-label="Hành động bài viết"
  >
    <button
      type="button"
      class="post-actions__item"
      disabled
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
      disabled
      title="Thích"
    >
      <span
        class="post-actions__icon"
        aria-hidden="true"
      >
        ♡
      </span>

      <span class="post-actions__label">
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
