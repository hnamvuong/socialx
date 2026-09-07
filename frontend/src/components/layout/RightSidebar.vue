<script setup lang="ts">
import { computed, ref } from 'vue'

import { useRoute, useRouter } from 'vue-router'

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

const route = useRoute()

const isSearchPage = computed(() => route.name === 'search')
</script>

<template>
  <aside class="right-sidebar">
    <div class="right-sidebar__inner">
      <form class="search-placeholder" v-if="!isSearchPage" @submit.prevent="submitSearch">
        <input
          v-model="searchQuery"
          class="search-placeholder__input"
          type="search"
          placeholder="Tìm kiếm SocialX"
          autocomplete="off"
          aria-label="Tìm kiếm SocialX"
        />
      </form>

      <section class="sidebar-card">
        <h2 class="sidebar-card__title">Xu hướng</h2>

        <div class="sidebar-card__empty">Nội dung xu hướng sẽ được bổ sung ở phần Explore.</div>
      </section>

      <section class="sidebar-card">
        <h2 class="sidebar-card__title">Gợi ý theo dõi</h2>

        <div class="sidebar-card__empty">Danh sách gợi ý sẽ được bổ sung ở phần Follow.</div>
      </section>
    </div>
  </aside>
</template>

<style lang="scss" src="@/assets/styles/components/layout/RightSidebar.scss"></style>
