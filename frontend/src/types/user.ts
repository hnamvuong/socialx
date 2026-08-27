export interface PublicUserProfile {
  id: number
  username: string
  display_name: string
  bio: string | null
  location: string | null
  website: string | null
  avatar_path: string | null
  avatar_url: string | null
  cover_path: string | null
  cover_url: string | null
  is_private: boolean
  is_verified: boolean
  created_at: string
}

export interface UserProfileResponse {
  data: {
    user: PublicUserProfile
  }
}

export interface UpdateProfilePayload {
  display_name: string
  bio: string | null
  location: string | null
  website: string | null
}

export interface UpdateProfileResponse {
  data: {
    user: PublicUserProfile
  }
}
