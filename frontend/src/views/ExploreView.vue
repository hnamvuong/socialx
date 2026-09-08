<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { RouterLink, useRouter } from 'vue-router'

import MainLayout from '@/layouts/MainLayout.vue'

import { getTrending } from '@/services/exploreService'

import type { TrendingHashtag } from '@/services/exploreService'

const router = useRouter()

const searchQuery = ref('')

const trends = ref<TrendingHashtag[]>([])

const loadingTrending = ref(false)

const trendingError = ref<string | null>(null)

function submitSearch(): void {
  const query = searchQuery.value.trim()

  if (!query) {
    return
  }

  void router.push({
    name: 'search',

    query: {
      q: query,
    },
  })
}

async function loadTrending(): Promise<void> {
  loadingTrending.value = true

  trendingError.value = null

  try {
    const response = await getTrending()

    trends.value = response.data.trends
  } catch {
    trends.value = []

    trendingError.value = 'Không thể tải xu hướng.'
  } finally {
    loadingTrending.value = false
  }
}

onMounted(() => {
  void loadTrending()
})
</script>

<template>
  <MainLayout>
    <section class="explore-view">
      <header class="explore-view__header">
        <h1 class="explore-view__title">Khám phá</h1>

        <form class="explore-view__search" @submit.prevent="submitSearch">
          <span class="explore-view__search-icon" aria-hidden="true">
            <svg
              viewBox="0 0 24 24"
              width="18"
              height="18"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="11" cy="11" r="7" />

              <path d="m20 20-3.5-3.5" />
            </svg>
          </span>

          <input
            v-model="searchQuery"
            class="explore-view__search-input"
            type="search"
            placeholder="Tìm kiếm trên SocialX"
            autocomplete="off"
          />
        </form>
      </header>

      <section class="explore-view__section">
        <div class="explore-view__section-header">
          <div>
            <h2 class="explore-view__section-title">Xu hướng dành cho bạn</h2>

            <p class="explore-view__section-description">
              Các chủ đề đang được quan tâm trong 6 giờ gần đây.
            </p>
          </div>
        </div>

        <div v-if="loadingTrending" class="explore-view__placeholder">Đang tải xu hướng...</div>

        <div v-else-if="trendingError" class="explore-view__placeholder">
          {{ trendingError }}
        </div>

        <div v-else-if="trends.length === 0" class="explore-view__placeholder">
          <strong> Chưa có xu hướng </strong>

          <span> Các hashtag đang được sử dụng sẽ xuất hiện tại đây. </span>
        </div>

        <div v-else class="explore-view__trends">
          <RouterLink
            v-for="(trend, index) in trends"
            :key="trend.id"
            class="explore-view__trend"
            :to="`/hashtag/${encodeURIComponent(trend.name)}`"
          >
            <span class="explore-view__trend-meta"> {{ index + 1 }} · Đang thịnh hành </span>

            <strong class="explore-view__trend-name"> #{{ trend.name }} </strong>

            <span class="explore-view__trend-stats">
              {{ trend.posts_last_6_hours }}
              bài viết ·
              {{ trend.unique_users }}
              người dùng
            </span>
          </RouterLink>
        </div>
      </section>

      <section class="explore-view__section">
        <div class="explore-view__section-header">
          <div>
            <h2 class="explore-view__section-title">Khám phá thêm</h2>

            <p class="explore-view__section-description">Tìm người dùng, bài viết hoặc hashtag.</p>
          </div>

          <RouterLink
            class="explore-view__search-link"
            :to="{
              name: 'search',
            }"
          >
            Tìm kiếm
          </RouterLink>
        </div>
      </section>
    </section>
  </MainLayout>
</template>

<style lang="scss" src="@/assets/styles/views/ExploreView.scss"></style>
