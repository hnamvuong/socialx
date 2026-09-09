export interface ConversationMember {
  id: number
  username: string
  display_name: string | null
  avatar_url: string | null
}

export interface ConversationLastMessage {
  id: number
  body: string | null
  sender_id: number
  has_attachments: boolean
  created_at: string
}

export interface Conversation {
  id: number
  type: 'direct' | 'group'
  title: string | null
  created_by: number | null

  members: ConversationMember[]

  other_member: ConversationMember | null

  last_message: ConversationLastMessage | null

  created_at: string
  updated_at: string
}

export interface ConversationListResponse {
  data: {
    conversations: Conversation[]
  }
}
