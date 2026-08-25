import { defineStore } from 'pinia'
import { ref } from 'vue'

import {
  login as loginRequest,
  logout as logoutRequest,
} from '@/services/authService'

import { getAuthToken } from '@/services/authToken'

import type {
  AuthenticatedUser,
  LoginPayload,
} from '@/types/auth'

export const useAuthStore = defineStore(
  'auth',
  () => {
    const user = ref<AuthenticatedUser | null>(null)
    const isAuthenticated = ref(
      getAuthToken() !== null,
    )

    async function login(
      payload: LoginPayload
    ): Promise<void> {
      const response = await loginRequest(payload)
      user.value = response.data.user
      isAuthenticated.value = true
    }

    async function logout(): Promise<void> {
      await logoutRequest()
      user.value = null
      isAuthenticated.value = false
    }

    return {
      user,
      isAuthenticated,
      login,
      logout,
    }
  }
)
