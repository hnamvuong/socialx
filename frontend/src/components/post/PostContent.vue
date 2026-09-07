<script setup lang="ts">
import {
  computed,
} from 'vue'
import {
  RouterLink,
} from 'vue-router'

const props =
  defineProps<{
    content:
      | string
      | null
      | undefined
  }>()

type ContentSegment =
  | {
      type: 'text'
      value: string
    }
  | {
      type: 'hashtag'
      value: string
      target: string
    }
  | {
      type: 'mention'
      value: string
      target: string
    }

const CONTENT_TOKEN_PATTERN =
  /(?<![\p{L}\p{N}_])(?:#([\p{L}\p{N}_]+)|@([A-Za-z0-9_]+))/gu

function parseContent(
  content: string,
): ContentSegment[] {
  if (!content) {
    return []
  }

  const segments:
    ContentSegment[] = []

  let lastIndex = 0

  for (
    const match of
      content.matchAll(
        CONTENT_TOKEN_PATTERN,
      )
  ) {
    const matchIndex =
      match.index

    if (
      matchIndex > lastIndex
    ) {
      segments.push({
        type: 'text',
        value:
          content.slice(
            lastIndex,
            matchIndex,
          ),
      })
    }

    const rawValue =
      match[0]

    const hashtag =
      match[1]

    const mention =
      match[2]

    if (hashtag) {
      segments.push({
        type: 'hashtag',
        value: rawValue,
        target:
          hashtag.toLowerCase(),
      })
    } else if (mention) {
      segments.push({
        type: 'mention',
        value: rawValue,
        target:
          mention.toLowerCase(),
      })
    }

    lastIndex =
      matchIndex
      + rawValue.length
  }

  if (
    lastIndex <
    content.length
  ) {
    segments.push({
      type: 'text',
      value:
        content.slice(
          lastIndex,
        ),
    })
  }

  return segments
}

const segments =
  computed(
    (): ContentSegment[] =>
      parseContent(
        props.content ?? '',
      ),
  )
</script>

<template>
  <span class="post-content">
    <template
      v-for="(segment, index) in segments"
      :key="index"
    >
      <RouterLink
        v-if="
          segment.type ===
          'hashtag'
        "
        class="post-content__link"
        :to="`/hashtag/${encodeURIComponent(segment.target)}`"
        @click.stop
      >
        {{ segment.value }}
      </RouterLink>

      <RouterLink
        v-else-if="
          segment.type ===
          'mention'
        "
        class="post-content__link"
        :to="`/@${encodeURIComponent(segment.target)}`"
        @click.stop
      >
        {{ segment.value }}
      </RouterLink>

      <template v-else>
        {{ segment.value }}
      </template>
    </template>
  </span>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/PostContent.scss"
></style>
