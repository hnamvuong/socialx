import api from '@/services/api'

import type { Post } from '@/types/post'

export interface SearchUser {
  id: number
  username: string
  display_name: string
}

export interface SearchHashtag {
  id: number
  name: string
}

export interface SearchUsersResponse {
  data: {
    users: SearchUser[]
  }
}

export interface SearchHashtagsResponse {
  data: {
    hashtags: SearchHashtag[]
  }
}

export interface SearchPostPagination {
  per_page: number
  next_cursor: string | null
  has_more: boolean
}

export interface SearchPostsResponse {
  data: {
    posts: Post[]
    pagination: SearchPostPagination
  }
}

export async function searchUsers(query: string): Promise<SearchUsersResponse> {
  const response = await api.get<SearchUsersResponse>('/search/users', {
    params: {
      q: query,
    },
  })

  return response.data
}

export async function searchPosts(
  query: string,
  cursor: string | null = null,
): Promise<SearchPostsResponse> {
  const response = await api.get<SearchPostsResponse>('/search/posts', {
    params: {
      q: query,
      ...(cursor
        ? {
            cursor,
          }
        : {}),
    },
  })

  return response.data
}

export async function searchHashtags(query: string): Promise<SearchHashtagsResponse> {
  const response = await api.get<SearchHashtagsResponse>('/search/hashtags', {
    params: {
      q: query,
    },
  })

  return response.data
}
