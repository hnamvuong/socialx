export interface MessageSender {
  id: number
  username: string
  display_name: string | null
  avatar_url: string | null
}

export interface MessageAttachment {
  id: number
  type: 'image' | 'file'
  url: string
  mime_type: string | null
  size: number | null
  width: number | null
  height: number | null
  sort_order: number
}

export interface Message {
  id: number
  conversation_id: number
  body: string | null
  sender: MessageSender
  attachments: MessageAttachment[]
  created_at: string
  updated_at: string
}

export interface MessagePagination {
  per_page: number
  next_cursor: string | null
  has_more: boolean
}

export interface MessageListResponse {
  data: {
    messages: Message[]
    pagination: MessagePagination
  }
}
