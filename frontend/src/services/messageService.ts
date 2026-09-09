import api from '@/services/api'

import type { Message, MessageListResponse } from '@/types/message'

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

export async function sendMessage(conversationId: number, formData: FormData): Promise<Message> {
  const response = await api.post<{
    data: {
      message: Message
    }
  }>(`/conversations/${conversationId}/messages`, formData)

  return response.data.data.message
}
