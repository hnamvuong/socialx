const TOKEN_KEY = 'socialx_auth_token'

export function getAuthToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setAuthToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function removeAuthToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}
