import api from './api'

import type {
  CreatePostResponse,
} from '@/types/post'

export async function createPost(
  content: string,
  media: File[],
): Promise<CreatePostResponse> {
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
    await api.post<CreatePostResponse>(
      '/posts',
      formData,
    )

  return response.data
}
