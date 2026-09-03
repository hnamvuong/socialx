import api from '@/services/api'

import type {
  FollowResponse,
} from '@/types/user'

export async function followUser(
  userId: number,
): Promise<FollowResponse> {
  const response =
    await api.post<FollowResponse>(
      `/users/${userId}/follow`,
    )

  return response.data
}

export async function unfollowUser(
  userId: number,
): Promise<FollowResponse> {
  const response =
    await api.delete<FollowResponse>(
      `/users/${userId}/follow`,
    )

  return response.data
}
