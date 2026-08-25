import api from './api'

import type {
  RegisterPayload,
  RegisterResponse,
  LoginPayload,
  LoginResponse
} from '@/types/auth'

import {
  removeAuthToken,
  setAuthToken,
} from './authToken'

export async function register(
  payload: RegisterPayload
): Promise<RegisterResponse> {
  const response = await api.post<RegisterResponse>(
    '/auth/register',
    payload,
  )
  return response.data
}

export async function login(
  payload: LoginPayload,
): Promise<LoginResponse> {
  const response = await api.post<LoginResponse>(
    '/auth/login',
    payload,
  )

  setAuthToken(response.data.data.token)

  return response.data
}

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout')
  } finally {
    removeAuthToken()
  }
}
