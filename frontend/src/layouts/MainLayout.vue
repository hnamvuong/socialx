<script setup lang="ts">
import LeftSidebar from '@/components/layout/LeftSidebar.vue'
import RightSidebar from '@/components/layout/RightSidebar.vue'
</script>

<template>
  <div class="app-shell">
    <div class="app-shell__container">
      <LeftSidebar />

      <main class="main-feed">
        <slot />
      </main>

      <RightSidebar />
    </div>
  </div>
</template>
