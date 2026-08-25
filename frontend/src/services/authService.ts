import api from './api'

import type {
  RegisterPayload,
  RegisterResponse,
} from '@/types/auth'

export async function register(
  payload: RegisterPayload
): Promise<RegisterResponse> {
  const response = await api.post<RegisterResponse>(
    '/auth/register',
    payload,
  )
  return response.data
}
