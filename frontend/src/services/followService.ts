import api from '@/services/api'

import type {
  FollowListResponse,
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

export async function getFollowers(
  username: string,
  page = 1,
): Promise<FollowListResponse> {
  const response =
    await api.get<FollowListResponse>(
      `/users/${encodeURIComponent(username)}/followers`,
      {
        params: {
          page,
        },
      },
    )

  return response.data
}

export async function getFollowing(
  username: string,
  page = 1,
): Promise<FollowListResponse> {
  const response =
    await api.get<FollowListResponse>(
      `/users/${encodeURIComponent(username)}/following`,
      {
        params: {
          page,
        },
      },
    )

  return response.data
}
