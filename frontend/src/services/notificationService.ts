import api from '@/services/api'

import type { NotificationListResponse } from '@/types/notification'

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
