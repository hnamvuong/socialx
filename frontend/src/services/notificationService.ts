import api from '@/services/api'

import type {
  MarkNotificationReadResponse,
  NotificationListResponse,
  UnreadCountResponse,
} from '@/types/notification'

export async function getNotifications(cursor?: string | null): Promise<NotificationListResponse> {
  const response = await api.get<NotificationListResponse>('/notifications', {
    params: cursor
      ? {
          cursor,
        }
      : undefined,
  })

  return response.data
}

export async function getUnreadNotificationCount(): Promise<number> {
  const response = await api.get<UnreadCountResponse>('/notifications/unread-count')

  return response.data.data.unread_count
}

export async function markNotificationAsRead(
  notificationId: number,
): Promise<MarkNotificationReadResponse> {
  const response = await api.patch<MarkNotificationReadResponse>(
    `/notifications/${notificationId}/read`,
  )

  return response.data
}

export async function markAllNotificationsAsRead(): Promise<void> {
  await api.patch('/notifications/read-all')
}
