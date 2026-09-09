<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import { RouterLink } from 'vue-router'

import AppAvatar from '@/components/ui/AppAvatar.vue'

import MainLayout from '@/layouts/MainLayout.vue'

import { getNotifications } from '@/services/notificationService'

import { useNotificationStore } from '@/stores/notification'

import type { SocialNotification } from '@/types/notification'

const notificationStore = useNotificationStore()

const notifications = ref<SocialNotification[]>([])

const loading = ref(false)

const loadingMore = ref(false)

const markingAll = ref(false)

const errorMessage = ref<string | null>(null)

const nextCursor = ref<string | null>(null)

const hasMore = ref(false)

const isEmpty = computed(
  (): boolean => !loading.value && notifications.value.length === 0 && !errorMessage.value,
)

function notificationText(notification: SocialNotification): string {
  switch (notification.type) {
    case 'like':
      return 'đã thích bài viết của bạn.'

    case 'follow':
      return 'đã theo dõi bạn.'

    case 'reply':
      return 'đã trả lời bài viết của bạn.'

    case 'mention':
      return 'đã nhắc đến bạn trong một bài viết.'

    default:
      return 'đã tương tác với bạn.'
  }
}

function notificationTarget(notification: SocialNotification): string {
  if (notification.type === 'follow' || !notification.post) {
    return `/@${notification.actor.username}`
  }

  return `/post/${notification.post.id}`
}

function actorName(notification: SocialNotification): string {
  return notification.actor.display_name || notification.actor.username
}

function formatDate(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const diff = Date.now() - date.getTime()

  const minute = 60 * 1000

  const hour = 60 * minute

  const day = 24 * hour

  if (diff < minute) {
    return 'Vừa xong'
  }

  if (diff < hour) {
    return `${Math.floor(diff / minute)} phút`
  }

  if (diff < day) {
    return `${Math.floor(diff / hour)} giờ`
  }

  if (diff < 7 * day) {
    return `${Math.floor(diff / day)} ngày`
  }

  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date)
}

function notificationIcon(notification: SocialNotification): string {
  switch (notification.type) {
    case 'like':
      return '♥'

    case 'follow':
      return '+'

    case 'reply':
      return '↩'

    case 'mention':
      return '@'

    default:
      return '•'
  }
}

function notificationIconClass(notification: SocialNotification): string {
  return `notification-item__type--${notification.type}`
}

async function loadNotifications(): Promise<void> {
  loading.value = true
  errorMessage.value = null

  try {
    const response = await getNotifications()

    notifications.value = response.data.notifications

    nextCursor.value = response.data.pagination.next_cursor

    hasMore.value = response.data.pagination.has_more

    await notificationStore.fetchUnreadCount()
  } catch {
    notifications.value = []

    errorMessage.value = 'Không thể tải thông báo.'
  } finally {
    loading.value = false
  }
}

async function loadMore(): Promise<void> {
  if (loadingMore.value || !hasMore.value || !nextCursor.value) {
    return
  }

  loadingMore.value = true
  errorMessage.value = null

  try {
    const response = await getNotifications(nextCursor.value)

    notifications.value.push(...response.data.notifications)

    nextCursor.value = response.data.pagination.next_cursor

    hasMore.value = response.data.pagination.has_more
  } catch {
    errorMessage.value = 'Không thể tải thêm thông báo.'
  } finally {
    loadingMore.value = false
  }
}

async function handleNotificationClick(notification: SocialNotification): Promise<void> {
  if (notification.read_at) {
    return
  }

  try {
    const readAt = await notificationStore.markAsRead(notification.id)

    notification.read_at = readAt
  } catch {
    /*
     * Navigation vẫn được phép tiếp tục
     * nếu mark-read thất bại.
     */
  }
}

async function handleMarkAllAsRead(): Promise<void> {
  if (markingAll.value || notificationStore.unreadCount === 0) {
    return
  }

  markingAll.value = true
  errorMessage.value = null

  try {
    await notificationStore.markAllAsRead()

    const readAt = new Date().toISOString()

    notifications.value = notifications.value.map((notification): SocialNotification => ({
      ...notification,

      read_at: notification.read_at ?? readAt,
    }))
  } catch {
    errorMessage.value = 'Không thể đánh dấu tất cả thông báo đã đọc.'
  } finally {
    markingAll.value = false
  }
}

async function refreshLatestNotifications(): Promise<void> {
  try {
    const response = await getNotifications()

    const existingIds = new Set(notifications.value.map((notification) => notification.id))

    const newNotifications = response.data.notifications.filter(
      (notification) => !existingIds.has(notification.id),
    )

    if (newNotifications.length === 0) {
      return
    }

    notifications.value.unshift(...newNotifications)
  } catch {
    /*
     * Realtime refresh lỗi
     * không làm hỏng page hiện tại.
     */
  }
}

watch(
  () => notificationStore.realtimeSequence,

  (current, previous) => {
    if (current === previous) {
      return
    }

    void refreshLatestNotifications()
  },
)

onMounted(() => {
  void loadNotifications()
})
</script>

<template>
  <MainLayout>
    <section class="notification-page">
      <header class="notification-page__header">
        <h1 class="notification-page__title">Thông báo</h1>

        <button
          v-if="notificationStore.unreadCount > 0"
          type="button"
          class="notification-page__mark-all"
          :disabled="markingAll"
          @click="handleMarkAllAsRead"
        >
          {{ markingAll ? 'Đang xử lý...' : 'Đánh dấu tất cả đã đọc' }}
        </button>
      </header>

      <div v-if="loading" class="notification-page__state">Đang tải thông báo...</div>

      <div v-else-if="errorMessage && notifications.length === 0" class="notification-page__state">
        <p>
          {{ errorMessage }}
        </p>

        <button type="button" class="notification-page__retry" @click="loadNotifications">
          Thử lại
        </button>
      </div>

      <div v-else-if="isEmpty" class="notification-page__empty">
        <strong> Chưa có thông báo </strong>

        <span> Các lượt thích, theo dõi, trả lời và nhắc đến bạn sẽ xuất hiện tại đây. </span>
      </div>

      <div v-else class="notification-page__list">
        <RouterLink
          v-for="notification in notifications"
          :key="notification.id"
          :to="notificationTarget(notification)"
          class="notification-item"
          :class="{
            'notification-item--unread': !notification.read_at,
          }"
          @click="handleNotificationClick(notification)"
        >
          <div class="notification-item__type" :class="notificationIconClass(notification)">
            {{ notificationIcon(notification) }}
          </div>

          <AppAvatar
            :src="notification.actor.avatar_url"
            :name="actorName(notification)"
            :size="44"
          />

          <div class="notification-item__body">
            <span
              v-if="!notification.read_at"
              class="notification-item__unread-dot"
              aria-label="Chưa đọc"
            />

            <p class="notification-item__message">
              <strong>
                {{ actorName(notification) }}
              </strong>

              {{ notificationText(notification) }}
            </p>

            <p v-if="notification.post?.content" class="notification-item__post">
              {{ notification.post.content }}
            </p>

            <time class="notification-item__time" :datetime="notification.created_at">
              {{ formatDate(notification.created_at) }}
            </time>
          </div>
        </RouterLink>

        <div v-if="errorMessage" class="notification-page__load-error">
          {{ errorMessage }}
        </div>

        <button
          v-if="hasMore"
          type="button"
          class="notification-page__load-more"
          :disabled="loadingMore"
          @click="loadMore"
        >
          {{ loadingMore ? 'Đang tải...' : 'Xem thêm' }}
        </button>
      </div>
    </section>
  </MainLayout>
</template>

<style lang="scss" src="@/assets/styles/views/NotificationView.scss"></style>
