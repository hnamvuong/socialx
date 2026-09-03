<script setup lang="ts">
import {
  computed,
  ref,
  watch,
} from 'vue'

import {
  RouterLink,
  useRoute,
  useRouter,
} from 'vue-router'

import axios from 'axios'

import MainLayout from '@/layouts/MainLayout.vue'

import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'

import {
  getFollowers,
  getFollowing,
} from '@/services/followService'

import type {
  FollowListUser,
} from '@/types/user'

const props = defineProps<{
  mode:
    | 'followers'
    | 'following'
}>()

const route =
  useRoute()

const router =
  useRouter()

const users =
  ref<FollowListUser[]>([])

const profileUser =
  ref<{
    id: number
    username: string
    display_name: string | null
  } | null>(null)

const currentPage =
  ref(1)

const hasMore =
  ref(false)

const loading =
  ref(true)

const loadingMore =
  ref(false)

const notFound =
  ref(false)

const errorMessage =
  ref<string | null>(
    null,
  )

const title =
  computed(() => {
    return props.mode === 'followers'
      ? 'Người theo dõi'
      : 'Đang theo dõi'
  })

function getUsername(): string {
  const username =
    route.params.username

  return Array.isArray(username)
    ? username[0] ?? ''
    : username ?? ''
}

async function loadUsers(): Promise<void> {
  const username =
    getUsername()

  if (!username) {
    notFound.value =
      true

    loading.value =
      false

    return
  }

  loading.value =
    true

  notFound.value =
    false

  errorMessage.value =
    null

  users.value =
    []

  profileUser.value =
    null

  currentPage.value =
    1

  hasMore.value =
    false

  try {
    const response =
      props.mode === 'followers'
        ? await getFollowers(
            username,
            1,
          )
        : await getFollowing(
            username,
            1,
          )

    profileUser.value =
      response.data.user

    users.value =
      response.data.users

    currentPage.value =
      response
        .data
        .pagination
        .current_page

    hasMore.value =
      response
        .data
        .pagination
        .has_more
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      notFound.value =
        true
    } else {
      errorMessage.value =
        'Không thể tải danh sách người dùng.'
    }
  } finally {
    loading.value =
      false
  }
}

async function loadMore(): Promise<void> {
  if (
    loadingMore.value
    || !hasMore.value
  ) {
    return
  }

  const username =
    getUsername()

  if (!username) {
    return
  }

  loadingMore.value =
    true

  try {
    const nextPage =
      currentPage.value + 1

    const response =
      props.mode === 'followers'
        ? await getFollowers(
            username,
            nextPage,
          )
        : await getFollowing(
            username,
            nextPage,
          )

    users.value.push(
      ...response.data.users,
    )

    currentPage.value =
      response
        .data
        .pagination
        .current_page

    hasMore.value =
      response
        .data
        .pagination
        .has_more
  } catch {
    errorMessage.value =
      'Không thể tải thêm người dùng.'
  } finally {
    loadingMore.value =
      false
  }
}

function goBack(): void {
  void router.push(
    `/@${getUsername()}`,
  )
}

watch(
  [
    () => route.params.username,
    () => props.mode,
  ],

  () => {
    void loadUsers()
  },

  {
    immediate: true,
  },
)
</script>

<template>
  <MainLayout>
    <section class="follow-list-page">
      <header class="follow-list-page__topbar">
        <AppButton
          variant="secondary"
          @click="goBack"
        >
          Quay lại
        </AppButton>

        <div>
          <strong>
            {{ title }}
          </strong>

          <div
            v-if="profileUser"
            class="follow-list-page__username"
          >
            @{{ profileUser.username }}
          </div>
        </div>
      </header>

      <template v-if="loading">
        <div
          v-for="index in 5"
          :key="index"
          class="follow-list-page__skeleton"
        >
          <AppSkeleton
            width="48px"
            height="48px"
            radius="50%"
          />

          <div class="follow-list-page__skeleton-content">
            <AppSkeleton
              width="160px"
              height="16px"
            />

            <AppSkeleton
              width="110px"
              height="14px"
            />

            <AppSkeleton
              width="80%"
              height="14px"
            />
          </div>
        </div>
      </template>

      <div
        v-else-if="notFound"
        class="follow-list-page__state"
      >
        <h1>
          Không tìm thấy tài khoản
        </h1>

        <p>
          Người dùng này không tồn tại hoặc hiện không khả dụng.
        </p>
      </div>

      <div
        v-else-if="errorMessage && users.length === 0"
        class="follow-list-page__state"
      >
        <h1>
          Đã xảy ra lỗi
        </h1>

        <p>
          {{ errorMessage }}
        </p>

        <AppButton
          variant="secondary"
          @click="loadUsers"
        >
          Thử lại
        </AppButton>
      </div>

      <div
        v-else-if="users.length === 0"
        class="follow-list-page__state"
      >
        <h2>
          {{
            mode === 'followers'
              ? 'Chưa có người theo dõi'
              : 'Chưa theo dõi ai'
          }}
        </h2>
      </div>

      <template v-else>
        <div class="follow-list-page__list">
          <RouterLink
            v-for="listUser in users"
            :key="listUser.id"
            :to="`/@${listUser.username}`"
            class="follow-list-page__user"
          >
            <div class="follow-list-page__avatar">
              <img
                v-if="listUser.avatar_url"
                :src="listUser.avatar_url"
                :alt="listUser.display_name || listUser.username"
              />

              <span v-else>
                {{
                  (
                    listUser.display_name
                    || listUser.username
                  )
                    .charAt(0)
                    .toUpperCase()
                }}
              </span>
            </div>

            <div class="follow-list-page__user-content">
              <div class="follow-list-page__name">
                <strong>
                  {{
                    listUser.display_name
                    || listUser.username
                  }}
                </strong>

                <span
                  v-if="listUser.is_verified"
                  aria-label="Tài khoản đã xác minh"
                >
                  ✓
                </span>
              </div>

              <div class="follow-list-page__user-username">
                @{{ listUser.username }}
              </div>

              <p
                v-if="listUser.bio"
                class="follow-list-page__bio"
              >
                {{ listUser.bio }}
              </p>
            </div>
          </RouterLink>
        </div>

        <p
          v-if="errorMessage"
          class="follow-list-page__load-more-error"
        >
          {{ errorMessage }}
        </p>

        <div
          v-if="hasMore"
          class="follow-list-page__load-more"
        >
          <AppButton
            variant="secondary"
            :disabled="loadingMore"
            @click="loadMore"
          >
            {{
              loadingMore
                ? 'Đang tải...'
                : 'Xem thêm'
            }}
          </AppButton>
        </div>
      </template>
    </section>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/FollowListView.scss"
></style>
