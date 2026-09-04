import api from './api'

import type {
  BookmarkResponse,
  CreatePostOptions,
  FollowingFeedResponse,
  LikeResponse,
  PostResponse,
  RepostResponse,
  ThreadResponse,
} from '@/types/post'

export async function createPost(
  content: string,
  media: File[],
  options: CreatePostOptions = {},
): Promise<PostResponse> {
  const formData =
    new FormData()

  const normalizedContent =
    content.trim()

  if (normalizedContent) {
    formData.append(
      'content',
      normalizedContent,
    )
  }

  if (
    options.quotedPostId
    !== undefined
    && options.quotedPostId
    !== null
  ) {
    formData.append(
      'quoted_post_id',
      String(
        options.quotedPostId,
      ),
    )
  }

  media.forEach((file) => {
    formData.append(
      'media[]',
      file,
    )
  })

  const response =
    await api.post<PostResponse>(
      '/posts',
      formData,
    )

  return response.data
}

export async function updatePost(
  postId: number,
  content: string,
): Promise<PostResponse> {
  const response =
    await api.patch<PostResponse>(
      `/posts/${postId}`,
      {
        content:
          content.trim() || null,
      },
    )

  return response.data
}

export async function deletePost(
  postId: number,
): Promise<void> {
  await api.delete(
    `/posts/${postId}`,
  )
}

export async function getPost(
  postId: number,
): Promise<PostResponse> {
  const response =
    await api.get<PostResponse>(
      `/posts/${postId}`,
    )

  return response.data
}

export async function createReply(
  parentPostId: number,
  content: string,
  media: File[],
): Promise<PostResponse> {
  const formData =
    new FormData()

  const normalizedContent =
    content.trim()

  if (normalizedContent) {
    formData.append(
      'content',
      normalizedContent,
    )
  }

  media.forEach((file) => {
    formData.append(
      'media[]',
      file,
    )
  })

  const response =
    await api.post<PostResponse>(
      `/posts/${parentPostId}/replies`,
      formData,
    )

  return response.data
}

export async function getThread(
  postId: number,
): Promise<ThreadResponse> {
  const response =
    await api.get<ThreadResponse>(
      `/posts/${postId}/thread`,
    )

  return response.data
}

export async function likePost(
  postId: number,
): Promise<LikeResponse> {
  const response =
    await api.post<LikeResponse>(
      `/posts/${postId}/like`
    )

  return response.data
}

export async function unlikePost(
  postId: number,
): Promise<LikeResponse> {
  const response =
    await api.delete<LikeResponse>(
      `/posts/${postId}/like`
    )

  return response.data
}

export async function repostPost(
  postId: number,
): Promise<RepostResponse> {
  const response =
    await api.post<RepostResponse>(
      `/posts/${postId}/repost`,
    )

  return response.data
}

export async function unrepostPost(
  postId: number,
): Promise<RepostResponse> {
  const response =
    await api.delete<RepostResponse>(
      `/posts/${postId}/repost`,
    )

  return response.data
}

export async function bookmarkPost(
  postId: number,
): Promise<BookmarkResponse> {
  const response =
    await api.post<BookmarkResponse>(
      `/posts/${postId}/bookmark`,
    )

  return response.data
}

export async function unbookmarkPost(
  postId: number,
): Promise<BookmarkResponse> {
  const response =
    await api.delete<BookmarkResponse>(
      `/posts/${postId}/bookmark`,
    )

  return response.data
}

export async function getFollowingFeed(
  page = 1,
): Promise<FollowingFeedResponse> {
  const response =
    await api.get<FollowingFeedResponse>(
      '/feed/following',
      {
        params: {
          page,
        },
      },
    )

  return response.data
}
