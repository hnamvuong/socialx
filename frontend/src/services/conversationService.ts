import api from '@/services/api'

import type { Conversation, ConversationListResponse } from '@/types/conversation'

export async function getConversations(): Promise<Conversation[]> {
  const response = await api.get<ConversationListResponse>('/conversations')

  return response.data.data.conversations
}

export async function getConversation(conversationId: number): Promise<Conversation> {
  const response = await api.get<{
    data: {
      conversation: Conversation
    }
  }>(`/conversations/${conversationId}`)

  return response.data.data.conversation
}

export async function createDirectConversation(userId: number): Promise<Conversation> {
  const response = await api.post<{
    data: {
      conversation: Conversation
    }
  }>('/conversations/direct', {
    user_id: userId,
  })

  return response.data.data.conversation
}
