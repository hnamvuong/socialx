<script setup lang="ts">
import { ref } from 'vue'

import { RouterLink, useRouter } from 'vue-router'

import MainLayout from '@/layouts/MainLayout.vue'

const router = useRouter()

const searchQuery = ref('')

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

              <path
                d="
                  m20 20
                  -3.5 -3.5
                "
              />
            </svg>
          </span>

          <input
            v-model="searchQuery"
            class="explore-view__search-input"
            type="search"
            placeholder="
              Tìm kiếm trên SocialX
            "
            autocomplete="off"
          />
        </form>
      </header>

      <section class="explore-view__section">
        <div class="explore-view__section-header">
          <div>
            <h2 class="explore-view__section-title">Xu hướng dành cho bạn</h2>

            <p class="explore-view__section-description">
              Các chủ đề đang được quan tâm trên SocialX.
            </p>
          </div>
        </div>

        <div class="explore-view__placeholder">
          <strong> Trending đang được chuẩn bị </strong>

          <span> Thuật toán xu hướng đang được xây dựng</span>
        </div>
      </section>

      <section class="explore-view__section">
        <div class="explore-view__section-header">
          <div>
            <h2 class="explore-view__section-title">Hashtag phổ biến</h2>

            <p class="explore-view__section-description">
              Khám phá các hashtag đang được sử dụng nhiều.
            </p>
          </div>
        </div>

        <div class="explore-view__placeholder">
          <strong> Chưa có dữ liệu xu hướng </strong>

          <span> Danh sách hashtag sẽ được nối với dữ liệu trending ở bài tiếp theo. </span>
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
