<script setup lang="ts">
import {
  ref,
  watch,
} from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import ProfileDetails from '@/components/profile/ProfileDetails.vue'
import ProfileHeader from '@/components/profile/ProfileHeader.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'
import {
  getUserProfile,
} from '@/services/userService'
import type {
  PublicUserProfile,
} from '@/types/user'
import axios from 'axios'

const route = useRoute()

const user =
  ref<PublicUserProfile | null>(null)

const loading = ref(true)

const notFound = ref(false)

const errorMessage =
  ref<string | null>(null)

function getUsername(): string {
  const username =
    route.params.username

  return Array.isArray(username)
    ? username[0] ?? ''
    : username ?? ''
}

async function loadProfile(): Promise<void> {
  const username = getUsername()

  if (!username) {
    notFound.value = true
    loading.value = false
    return
  }

  loading.value = true

  notFound.value = false
  errorMessage.value = null
  user.value = null

  try {
    const response = await getUserProfile(username)

    user.value = response.data.user
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 404
    ) {
      notFound.value = true
    } else {
      errorMessage.value =
        'Không thể tải thông tin người dùng.'
    }
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.username,
  () => {
    void loadProfile()
  },
  {
    immediate: true,
  },
)
</script>

<template>
  <MainLayout>
    <section class="profile-page">
      <header class="profile-page__topbar">
        <div>
          <strong>
            {{ user?.display_name || 'Hồ sơ' }}
          </strong>

          <div
            v-if="user"
            class="profile-page__topbar-username"
          >
            @{{ user.username }}
          </div>
        </div>
      </header>

      <template v-if="loading">
        <div class="profile-page__loading-cover">
          <AppSkeleton
            width="100%"
            height="100%"
            radius="0"
          />
        </div>

        <div class="profile-page__loading-content">
          <AppSkeleton
            width="136px"
            height="136px"
            radius="50%"
          />

          <AppSkeleton
            width="180px"
            height="22px"
          />

          <AppSkeleton
            width="120px"
            height="14px"
          />

          <AppSkeleton
            width="85%"
            height="14px"
          />
        </div>
      </template>

      <template v-else-if="user">
        <ProfileHeader
          :user="user"
        />

        <ProfileDetails
          :user="user"
        />

        <div class="profile-page__content-placeholder">
          Nội dung của người dùng sẽ được bổ sung khi hệ thống Post được xây dựng.
        </div>
      </template>

      <div
        v-else-if="notFound"
        class="profile-page__state"
      >
        <h1>
          Không tìm thấy tài khoản
        </h1>

        <p>
          Người dùng này không tồn tại hoặc hiện không khả dụng.
        </p>
      </div>

      <div
        v-else
        class="profile-page__state"
      >
        <h1>
          Đã xảy ra lỗi
        </h1>

        <p>
          {{ errorMessage }}
        </p>

        <AppButton
          variant="secondary"
          @click="loadProfile"
        >
          Thử lại
        </AppButton>
      </div>
    </section>
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/ProfileView.scss"
></style>
