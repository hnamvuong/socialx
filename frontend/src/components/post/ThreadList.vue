<script setup lang="ts">
import {
  computed,
} from 'vue'
import ThreadItem from '@/components/post/ThreadItem.vue'
import {
  buildThreadTree,
} from '@/utils/thread'

import type {
  Post,
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
} from '@/types/post'

const props = defineProps<{
  root: Post
  replies: Post[]
}>()

defineEmits<{
  updated: [post: Post]
  deleted: [postId: number]
  reply: [post: Post]
  likeChanged: [state: PostLikeState]
  repostChanged: [state: PostRepostState]
  quoteCreated: [post: Post]
  bookmarkChanged: [state: PostBookmarkState]
}>()

const tree =
  computed(() => {
    return buildThreadTree(
      props.root,
      props.replies,
    )
  })
</script>

<template>
  <section class="thread-list">
    <ThreadItem
      v-for="
        child in tree.children
      "
      :key="child.post.id"
      :node="child"
      :depth="0"
      @updated="
        $emit(
          'updated',
          $event
        )
      "
      @deleted="
        $emit(
          'deleted',
          $event
        )
      "
      @reply="
        $emit(
          'reply',
          $event
        )
      "
      @like-changed="
        $emit(
          'likeChanged',
          $event
        )
      "
      @repost-changed="
        $emit(
          'repostChanged',
          $event
        )
      "
      @quote-created="
        $emit(
          'quoteCreated',
          $event
        )
      "
      @bookmark-changed="
        $emit(
          'bookmarkChanged',
          $event
        )
      "
    />
  </section>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/ThreadList.scss"
></style>
