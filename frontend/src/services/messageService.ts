import api from '@/services/api'

import type { MessageListResponse } from '@/types/message'

export async function getMessages(
  conversationId: number,
  cursor?: string | null,
): Promise<MessageListResponse['data']> {
  const response = await api.get<MessageListResponse>(`/conversations/${conversationId}/messages`, {
    params: cursor
      ? {
          cursor,
        }
      : undefined,
  })

  return response.data.data
}
