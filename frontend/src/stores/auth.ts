import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import {
  getCurrentUser,
  login as loginRequest,
  logout as logoutRequest,
} from '@/services/authService'

import { getAuthToken, removeAuthToken } from '@/services/authToken'

import type { AuthenticatedUser, LoginPayload } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthenticatedUser | null>(null)

  const isInitialized = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  async function initialize(): Promise<void> {
    const token = getAuthToken()

    if (!token) {
      user.value = null
      isInitialized.value = true

      return
    }

    try {
      const response = await getCurrentUser()

      user.value = response.data.user
    } catch {
      removeAuthToken()
      user.value = null
    } finally {
      isInitialized.value = true
    }
  }

  async function login(payload: LoginPayload): Promise<void> {
    const response = await loginRequest(payload)
    user.value = response.data.user
  }

  async function logout(): Promise<void> {
    try {
      await logoutRequest()
    } finally {
      removeAuthToken()
      user.value = null
    }
  }

  return {
    user,
    isInitialized,
    isAuthenticated,
    initialize,
    login,
    logout,
  }
})
