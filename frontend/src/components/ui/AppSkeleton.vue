<script setup lang="ts">
withDefaults(
  defineProps<{
    width?: string
    height?: string
    radius?: string
  }>(),
  {
    width: '100%',
    height: '16px',
    radius: '8px',
  },
)
</script>

<template>
  <div
    class="app-skeleton"
    :style="{
      width,
      height,
      borderRadius: radius,
    }"
    aria-hidden="true"
  />
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/AppSkeleton.scss"
></style>
