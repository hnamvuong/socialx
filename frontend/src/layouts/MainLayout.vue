<script setup lang="ts">
import BottomNavigation from '@/components/layout/BottomNavigation.vue'
import LeftSidebar from '@/components/layout/LeftSidebar.vue'
import RightSidebar from '@/components/layout/RightSidebar.vue'

withDefaults(
  defineProps<{
    hideRightSidebar?: boolean
  }>(),
  {
    hideRightSidebar: false,
  },
)
</script>

<template>
  <div class="app-shell">
    <div
      class="app-shell__container"
      :class="{
        'app-shell__container--without-right-sidebar': hideRightSidebar,
      }"
    >
      <LeftSidebar />

      <main class="main-feed">
        <slot />
      </main>

      <RightSidebar v-if="!hideRightSidebar" />
    </div>

    <BottomNavigation />
  </div>
</template>

<style lang="scss" src="@/assets/styles/layouts/MainLayout.scss"></style>
