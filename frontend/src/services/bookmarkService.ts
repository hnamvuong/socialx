import api from '@/services/api'

import type {
  BookmarkListResponse,
} from '@/types/post'

export async function getBookmarks(
  page = 1,
): Promise<BookmarkListResponse> {
  const response =
    await api.get<BookmarkListResponse>(
      '/bookmarks',
      {
        params: {
          page,
        },
      },
    )

  return response.data
}
