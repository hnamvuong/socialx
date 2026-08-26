<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    src?: string | null
    alt?: string
    name?: string
    size?: number
  }>(),
  {
    src: null,
    alt: '',
    name: '',
    size: 40,
  },
)

const initials = computed(() => {
  const value = props.name.trim()

  if (!value) {
    return '?'
  }

  return value
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase()
})
</script>

<template>
  <div
    class="app-avatar"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
    }"
  >
    <img
      v-if="src"
      class="app-avatar__image"
      :src="src"
      :alt="alt || name"
    >

    <span
      v-else
      class="app-avatar__fallback"
      aria-hidden="true"
    >
      {{ initials }}
    </span>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/AppAvatar.scss"
></style>
