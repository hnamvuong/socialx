import api from './api'

import type {
  UpdateProfilePayload,
  UpdateProfileResponse,
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

export async function updateProfile(
  payload: UpdateProfilePayload,
): Promise<UpdateProfileResponse> {
  const response =
    await api.patch<UpdateProfileResponse>(
      '/profile',
      payload,
    )

  return response.data
}

export async function uploadAvatar(
  file: File,
): Promise<UpdateProfileResponse> {
  const formData = new FormData()

  formData.append(
    'avatar',
    file,
  )

  const response =
    await api.post<UpdateProfileResponse>(
      '/profile/avatar',
      formData,
    )

  return response.data
}

export async function uploadCover(
  file: File,
): Promise<UpdateProfileResponse> {
  const formData = new FormData()

  formData.append(
    'cover',
    file,
  )

  const response =
    await api.post<UpdateProfileResponse>(
      '/profile/cover',
      formData,
    )

  return response.data
}
