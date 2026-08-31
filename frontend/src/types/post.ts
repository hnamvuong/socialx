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
  quoted_post_id: number | null
  content: string | null
  created_at: string
  updated_at: string

  likes_count: number
  liked_by_me: boolean

  reposts_count: number
  reposted_by_me: boolean

  bookmarked_by_me: boolean

  user: PostAuthor

  media: PostMedia[]

  quoted_post: QuotedPost | null
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

export interface LikeResponse {
  data: {
    liked: boolean
    likes_count: number
  }
}

export interface PostLikeState {
  postId: number
  liked: boolean
  likesCount: number
}

export interface RepostResponse {
  data: {
    reposted: boolean
    reposts_count: number
  }
}

export interface QuotedPost {
  id: number
  content: string | null
  created_at: string

  user: PostAuthor
  media: PostMedia[]
}

export interface CreatePostOptions {
  quotedPostId?: number | null
}

export interface PostRepostState {
  postId: number
  reposted: boolean
  repostsCount: number
}

export interface PostBookmarkState {
  postId: number
  bookmarked: boolean
  phase: 'optimistic' | 'confirmed' | 'rollback'
}

export interface BookmarkResponse {
  data: {
    bookmarked: boolean
  }
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  has_more: boolean
}

export interface BookmarkListResponse {
  data: {
    posts: Post[]
    pagination: PaginationMeta
  }
}
