export interface PostAuthor {
  id: number
  username: string
  display_name: string
  avatar_url: string | null
  is_verified: boolean
}

export interface PostMedia {
  id: number
  type: string
  path: string
  url: string
  mime_type: string | null
  width: number | null
  height: number | null
  sort_order: number
}

export interface Post {
  id: number
  parent_post_id: number | null
  root_post_id: number | null
  content: string | null
  created_at: string
  updated_at: string

  user: PostAuthor

  media: PostMedia[]
}

export interface PostResponse {
  data: {
    post: Post
  }
}

export interface ThreadResponse {
  data: {
    root: Post
    replies: Post[]
  }
}

export interface ThreadNode {
  post: Post
  children: ThreadNode[]
}
