import api from '@/services/api'

import type {
  AcceptFollowRequestResponse,
  FollowListResponse,
  FollowRequestListResponse,
  FollowResponse,
  RejectFollowRequestResponse,
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

export async function getFollowRequests(
  page = 1,
): Promise<FollowRequestListResponse> {
  const response =
    await api.get<FollowRequestListResponse>(
      '/follow-requests',
      {
        params: {
          page,
        },
      },
    )

  return response.data
}

export async function acceptFollowRequest(
  followRequestId: number,
): Promise<AcceptFollowRequestResponse> {
  const response =
    await api.post<AcceptFollowRequestResponse>(
      `/follow-requests/${followRequestId}/accept`,
    )

  return response.data
}

export async function rejectFollowRequest(
  followRequestId: number,
): Promise<RejectFollowRequestResponse> {
  const response =
    await api.delete<RejectFollowRequestResponse>(
      `/follow-requests/${followRequestId}/reject`,
    )

  return response.data
}
