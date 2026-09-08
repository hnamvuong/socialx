import { ref } from 'vue'

import { defineStore } from 'pinia'

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

  return {
    unreadCount,
    loadingUnreadCount,

    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
  }
})
