import { ref } from 'vue'

import { defineStore } from 'pinia'

import echo from '@/services/echo'

import {
  getUnreadNotificationCount,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from '@/services/notificationService'

export const useNotificationStore = defineStore('notification', () => {
  const unreadCount = ref(0)

  const loadingUnreadCount = ref(false)

  async function fetchUnreadCount(): Promise<void> {
    if (loadingUnreadCount.value) {
      return
    }

    loadingUnreadCount.value = true

    try {
      unreadCount.value = await getUnreadNotificationCount()
    } finally {
      loadingUnreadCount.value = false
    }
  }

  async function markAsRead(notificationId: number): Promise<string | null> {
    const response = await markNotificationAsRead(notificationId)

    unreadCount.value = response.data.unread_count

    return response.data.notification.read_at
  }

  async function markAllAsRead(): Promise<void> {
    await markAllNotificationsAsRead()

    unreadCount.value = 0
  }

  const realtimeSequence = ref(0)

  const latestRealtimeNotificationId = ref<number | null>(null)

  let realtimeUserId: number | null = null

  function startRealtime(userId: number): void {
    if (realtimeUserId === userId) {
      return
    }

    if (realtimeUserId !== null) {
      echo.leave(`notifications.${realtimeUserId}`)
    }

    realtimeUserId = userId

    echo
      .private(`notifications.${userId}`)
      .listen('.notification.created', (event: RealtimeNotificationCreated) => {
        unreadCount.value += 1

        latestRealtimeNotificationId.value = event.notification_id

        realtimeSequence.value += 1
      })
  }

  function stopRealtime(): void {
    if (realtimeUserId === null) {
      return
    }

    echo.leave(`notifications.${realtimeUserId}`)

    realtimeUserId = null
  }

  return {
    unreadCount,
    loadingUnreadCount,
    realtimeSequence,
    latestRealtimeNotificationId,

    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    startRealtime,
    stopRealtime,
  }
})

interface RealtimeNotificationCreated {
  notification_id: number
}
