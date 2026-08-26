export interface RegisterPayload {
  username: string
  display_name: string
  email: string
  password: string
  password_confirmation: string
}

export interface RegisteredUser {
  id: number
  username: string
  display_name: string
  email: string
  is_private: boolean
  is_verified: boolean
  created_at: string
}

export interface RegisterResponse {
  message: string

  data: {
    user: RegisteredUser
  }
}

export interface LoginPayload {
  email: string
  password: string
}

export interface AuthenticatedUser {
  id: number
  username: string
  display_name: string
  email: string
  is_private: boolean
  is_verified: boolean
  created_at: string
}

export interface LoginResponse {
  message: string

  data: {
    token: string
    user: AuthenticatedUser
  }
}

export interface CurrentUserResponse {
  data: {
    user: AuthenticatedUser
  }
}

export interface ForgotPasswordPayload {
  email: string
}

export interface ForgotPasswordResponse {
  message: string
}

export interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface ResetPasswordResponse {
  message: string
}
