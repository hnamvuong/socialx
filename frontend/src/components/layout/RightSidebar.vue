<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

import { RouterLink, useRoute, useRouter } from 'vue-router'

import { getTrending } from '@/services/exploreService'

import type { TrendingHashtag } from '@/services/exploreService'

const route = useRoute()

const router = useRouter()

const searchQuery = ref('')

const trends = ref<TrendingHashtag[]>([])

const loadingTrending = ref(false)

const trendingError = ref<string | null>(null)

const isSearchPage = computed((): boolean => route.name === 'search')

const isExplorePage = computed((): boolean => route.name === 'explore')

const showSearch = computed((): boolean => !isSearchPage.value && !isExplorePage.value)

const showTrending = computed((): boolean => !isExplorePage.value)

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

    /*
     * Sidebar chỉ cần một số trend đầu.
     * Explore page vẫn hiển thị danh sách đầy đủ hơn.
     */
    trends.value = response.data.trends.slice(0, 5)
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
  <aside class="right-sidebar">
    <div class="right-sidebar__inner">
      <!--
        Search box:
        Ẩn trên chính Search page
        để tránh xuất hiện 2 ô search.
      -->
      <form v-if="showSearch" class="search-placeholder" @submit.prevent="submitSearch">
        <span class="search-placeholder__icon" aria-hidden="true">
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
          class="search-placeholder__input"
          type="search"
          placeholder="
            Tìm kiếm SocialX
          "
          autocomplete="off"
          aria-label="
            Tìm kiếm SocialX
          "
        />
      </form>

      <!--
        WHAT'S HAPPENING
      -->
      <section class="sidebar-card" v-if="showTrending">
        <h2 class="sidebar-card__title">Xu hướng</h2>

        <div v-if="loadingTrending" class="sidebar-card__empty">Đang tải xu hướng...</div>

        <div v-else-if="trendingError" class="sidebar-card__empty">
          {{ trendingError }}
        </div>

        <div v-else-if="trends.length === 0" class="sidebar-card__empty">Chưa có xu hướng.</div>

        <div v-else class="sidebar-card__trends">
          <RouterLink
            v-for="(trend, index) in trends"
            :key="trend.id"
            class="sidebar-card__trend"
            :to="`/hashtag/${encodeURIComponent(trend.name)}`"
          >
            <span class="sidebar-card__trend-meta">
              {{ index + 1 }}
              · Đang thịnh hành
            </span>

            <strong class="sidebar-card__trend-name"> #{{ trend.name }} </strong>

            <span class="sidebar-card__trend-stats">
              {{ trend.posts_last_6_hours }}
              bài viết
            </span>
          </RouterLink>

          <RouterLink
            class="sidebar-card__more"
            :to="{
              name: 'explore',
            }"
          >
            Xem thêm
          </RouterLink>
        </div>
      </section>

      <!--
        WHO TO FOLLOW
        giữ nguyên placeholder.
      -->
      <section class="sidebar-card">
        <h2 class="sidebar-card__title">Gợi ý theo dõi</h2>

        <div class="sidebar-card__empty">Danh sách gợi ý sẽ được bổ sung ở phần Follow.</div>
      </section>
    </div>
  </aside>
</template>

<style lang="scss" src="@/assets/styles/components/layout/RightSidebar.scss"></style>
