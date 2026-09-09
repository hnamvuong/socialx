<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'

import { RouterLink } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import { useNotificationStore } from '@/stores/notification'

const authStore = useAuthStore()

const notificationStore = useNotificationStore()

const profilePath = computed(() => {
  if (!authStore.user?.username) {
    return null
  }

  return `/@${authStore.user.username}`
})

watch(
  () => authStore.user?.id,

  (userId) => {
    if (!userId) {
      notificationStore.stopRealtime()

      return
    }

    notificationStore.startRealtime(userId)
  },

  {
    immediate: true,
  },
)

onMounted(() => {
  void notificationStore.fetchUnreadCount()

  if (authStore.user?.id) {
    notificationStore.startRealtime(authStore.user.id)
  }
})
</script>

<template>
  <nav class="bottom-navigation">
    <RouterLink to="/" class="bottom-navigation__item"> Trang chủ </RouterLink>

    <RouterLink to="/explore" class="bottom-navigation__item"> Khám phá </RouterLink>

    <RouterLink
      to="/notifications"
      class="bottom-navigation__item bottom-navigation__item--notifications"
    >
      <span class="bottom-navigation__label"> Thông báo </span>

      <span
        v-if="notificationStore.unreadCount > 0"
        class="bottom-navigation__badge"
        :aria-label="`${notificationStore.unreadCount} thông báo chưa đọc`"
      />
    </RouterLink>

    <RouterLink to="/messages" class="bottom-navigation__item"> Tin nhắn </RouterLink>

    <RouterLink v-if="profilePath" :to="profilePath" class="bottom-navigation__item">
      Hồ sơ
    </RouterLink>

    <button v-else type="button" class="bottom-navigation__item" disabled>Hồ sơ</button>
  </nav>
</template>

<style lang="scss" src="@/assets/styles/components/layout/BottomNavigation.scss"></style>
