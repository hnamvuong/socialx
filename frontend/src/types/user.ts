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
  followers_count: number
  following_count: number
  relationship: FollowRelationship
  following: boolean
  follow_requested: boolean
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

export type FollowRelationship =
  | 'self'
  | 'none'
  | 'following'
  | 'requested'

export interface FollowResponse {
  data: {
    relationship:
      | 'none'
      | 'following'
      | 'requested'

    following: boolean
    follow_requested: boolean
  }
}

export interface FollowListUser {
  id: number
  username: string
  display_name: string | null

  bio: string | null
  avatar_url: string | null

  is_private: boolean
  is_verified: boolean
}

export interface FollowListResponse {
  data: {
    user: {
      id: number
      username: string
      display_name: string | null
    }

    users: FollowListUser[]

    pagination: {
      current_page: number
      last_page: number
      per_page: number
      total: number
      has_more: boolean
    }
  }
}

export interface FollowRequestItem {
  id: number
  status: 'pending'
  created_at: string

  requester: FollowListUser
}

export interface FollowRequestListResponse {
  data: {
    requests: FollowRequestItem[]

    pagination: {
      current_page: number
      last_page: number
      per_page: number
      total: number
      has_more: boolean
    }
  }
}

export interface AcceptFollowRequestResponse {
  data: {
    accepted: boolean
    requester_id: number
    following: boolean
  }
}

export interface RejectFollowRequestResponse {
  data: {
    rejected: boolean
    requester_id: number
  }
}
