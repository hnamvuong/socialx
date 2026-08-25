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
