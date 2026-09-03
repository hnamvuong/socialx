<script setup lang="ts">
import {
  computed,
  ref,
  watch,
} from 'vue'

import { useRoute } from 'vue-router'

import axios from 'axios'

import MainLayout from '@/layouts/MainLayout.vue'

import ProfileDetails from '@/components/profile/ProfileDetails.vue'
import ProfileHeader from '@/components/profile/ProfileHeader.vue'
import FollowButton from '@/components/profile/FollowButton.vue'

import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'

import EditProfileModal from '@/components/profile/EditProfileModal.vue'

import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

import {
  getUserProfile,
} from '@/services/userService'

import {
  followUser,
  unfollowUser,
} from '@/services/followService'

import type {
  PublicUserProfile,
} from '@/types/user'

const route = useRoute()

const user =
  ref<PublicUserProfile | null>(null)

const loading = ref(true)

const notFound = ref(false)

const errorMessage =
  ref<string | null>(null)

const authStore = useAuthStore()

const toastStore = useToastStore()

const editProfileOpen = ref(false)

const followLoading = ref(false)

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

const isOwnProfile = computed(() => {
  return (
    !!user.value &&
    !!authStore.user &&
    user.value.username ===
      authStore.user.username
  )
})

function handleProfileUpdated(
  updatedUser: PublicUserProfile,
): void {
  user.value = updatedUser

  editProfileOpen.value = false

  toastStore.success(
    'Đã cập nhật hồ sơ.',
  )
}

function handleProfileMediaUpdated(
  updatedUser: PublicUserProfile,
): void {
  user.value = updatedUser

  toastStore.success(
    'Đã cập nhật hình ảnh hồ sơ.'
  )
}

async function handleFollowToggle(): Promise<void> {
  if (
    followLoading.value
    || !user.value
  ) {
    return
  }

  if (
    !authStore.isAuthenticated
  ) {
    toastStore.error(
      'Bạn cần đăng nhập để theo dõi người dùng.',
    )

    return
  }

  if (isOwnProfile.value) {
    return
  }

  const currentUser = user.value

  const previousRelationship = currentUser.relationship

  const previousFollowing = currentUser.following

  const previousFollowRequested = currentUser.follow_requested

  const previousFollowersCount = currentUser.followers_count

  const shouldFollow = previousRelationship === 'none'

  followLoading.value = true

  /*
   * Optimistic update
   */
  if (shouldFollow) {
    if (currentUser.is_private) {
      currentUser.relationship = 'requested'

      currentUser.following = false

      currentUser.follow_requested = true
    } else {
      currentUser.relationship = 'following'

      currentUser.following = true

      currentUser.follow_requested = false

      currentUser.followers_count = previousFollowersCount + 1
    }
  } else {
    currentUser.relationship = 'none'

    currentUser.following = false

    currentUser.follow_requested = false

    if (
      previousRelationship
        === 'following'
    ) {
      currentUser.followers_count =
        Math.max(
          0,
          previousFollowersCount - 1,
        )
    }
  }

  try {
    const response =
      shouldFollow
        ? await followUser(
            currentUser.id,
          )
        : await unfollowUser(
            currentUser.id,
          )

    const serverRelationship = response.data.relationship

    const wasFollowing = previousRelationship === 'following'

    const isFollowing = serverRelationship === 'following'

    currentUser.relationship = serverRelationship

    currentUser.following = response.data.following

    currentUser.follow_requested = response.data.follow_requested

    /*
     * Server response hiện chưa trả followers_count.
     *
     * Vì vậy count được tính từ trạng thái
     * trước request và trạng thái server xác nhận.
     */
    if (
      !wasFollowing
      && isFollowing
    ) {
      currentUser.followers_count =
        previousFollowersCount + 1
    } else if (
      wasFollowing
      && !isFollowing
    ) {
      currentUser.followers_count =
        Math.max(
          0,
          previousFollowersCount - 1,
        )
    } else {
      currentUser.followers_count = previousFollowersCount
    }
  } catch (error: unknown) {
    /*
     * Rollback chính xác về state trước click.
     */
    currentUser.relationship = previousRelationship

    currentUser.following = previousFollowing

    currentUser.follow_requested = previousFollowRequested

    currentUser.followers_count = previousFollowersCount

    if (
      axios.isAxiosError(error)
      && error.response?.status === 401
    ) {
      toastStore.error(
        'Phiên đăng nhập không còn hợp lệ.',
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      toastStore.error(
        'Người dùng không còn khả dụng.',
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 422
    ) {
      toastStore.error(
        'Không thể theo dõi tài khoản này.',
      )

      return
    }

    toastStore.error(
      shouldFollow
        ? 'Không thể theo dõi người dùng.'
        : 'Không thể cập nhật trạng thái theo dõi.',
    )
  } finally {
    followLoading.value =
      false
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
        >
          <template #actions>
            <AppButton
              v-if="isOwnProfile"
              variant="secondary"
              @click="
                editProfileOpen = true
              "
            >
              Chỉnh sửa hồ sơ
            </AppButton>

            <FollowButton
              v-else
              :relationship="
                user.relationship
              "
              :loading="
                followLoading
              "
              @toggle="
                handleFollowToggle
              "
            />
          </template>
        </ProfileHeader>

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
          @click="
            loadProfile
          "
        >
          Thử lại
        </AppButton>
      </div>
    </section>

    <EditProfileModal
      v-if="
        user
        && isOwnProfile
      "
      :open="
        editProfileOpen
      "
      :user="
        user
      "
      @close="
        editProfileOpen = false
      "
      @updated="
        handleProfileUpdated
      "
      @media-updated="
        handleProfileMediaUpdated
      "
    />
  </MainLayout>
</template>

<style
  lang="scss"
  src="@/assets/styles/views/ProfileView.scss"
></style>
