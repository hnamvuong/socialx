export type NotificationType = 'like' | 'follow' | 'reply' | 'mention'

export interface NotificationActor {
  id: number
  username: string
  display_name: string | null
  avatar_url: string | null
}

export interface NotificationPost {
  id: number
  content: string | null
}

export interface SocialNotification {
  id: number
  type: NotificationType

  read_at: string | null
  created_at: string

  actor: NotificationActor

  post: NotificationPost | null
}

export interface NotificationPagination {
  per_page: number
  next_cursor: string | null
  has_more: boolean
}

export interface NotificationListResponse {
  data: {
    notifications: SocialNotification[]

    pagination: NotificationPagination
  }
}
