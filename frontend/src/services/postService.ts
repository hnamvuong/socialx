import api from './api'

import type {
  PostResponse,
} from '@/types/post'

export async function createPost(
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
