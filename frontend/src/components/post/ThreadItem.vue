<script setup lang="ts">
import PostCard from '@/components/post/PostCard.vue'
import type {
  Post,
  PostBookmarkState,
  PostLikeState,
  PostRepostState,
  ThreadNode,
} from '@/types/post'

defineProps<{
  node: ThreadNode
  depth?: number
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
</script>

<template>
  <div
    class="thread-item"
    :class="{
      'thread-item--nested':
        (depth ?? 0) > 0,
    }"
  >
    <PostCard
      :post="node.post"
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

    <div
      v-if="
        node.children.length > 0
      "
      class="thread-item__children"
    >
      <ThreadItem
        v-for="
          child in node.children
        "
        :key="child.post.id"
        :node="child"
        :depth="
          (depth ?? 0) + 1
        "
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
        @bookmark-changed="
          $emit(
            'bookmarkChanged',
            $event
          )
        "
      />
    </div>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/ThreadItem.scss"
></style>
