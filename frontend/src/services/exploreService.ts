import api from '@/services/api'

export interface TrendingHashtag {
  id: number
  name: string

  posts_last_hour: number
  posts_last_6_hours: number
  unique_users: number

  trend_score: number
}

export interface TrendingResponse {
  data: {
    trends: TrendingHashtag[]
  }
}

export async function getTrending(): Promise<TrendingResponse> {
  const response = await api.get<TrendingResponse>('/explore/trending')

  return response.data
}
