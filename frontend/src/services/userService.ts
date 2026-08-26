import api from './api'

import type {
  UserProfileResponse,
} from '@/types/user'

export async function getUserProfile(
  username: string,
): Promise<UserProfileResponse> {
  const response =
    await api.get<UserProfileResponse>(
      `/users/${encodeURIComponent(username)}`,
    )

  return response.data
}
