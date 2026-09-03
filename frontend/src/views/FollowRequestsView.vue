<script setup lang="ts">
import {
  ref,
  onMounted,
} from 'vue'

import {
  RouterLink,
} from 'vue-router'

import axios from 'axios'

import MainLayout from '@/layouts/MainLayout.vue'

import AppButton from '@/components/ui/AppButton.vue'
import AppSkeleton from '@/components/ui/AppSkeleton.vue'

import {
  acceptFollowRequest,
  getFollowRequests,
  rejectFollowRequest,
} from '@/services/followService'

import {
  useToastStore,
} from '@/stores/toast'

import type {
  FollowRequestItem,
} from '@/types/user'

const toastStore =
  useToastStore()

const requests =
  ref<FollowRequestItem[]>([])

const loading =
  ref(true)

const loadingMore =
  ref(false)

const currentPage =
  ref(1)

const hasMore =
  ref(false)

const errorMessage =
  ref<string | null>(
    null,
  )

const processingIds =
  ref<number[]>([])

function isProcessing(
  requestId: number,
): boolean {
  return processingIds.value
    .includes(
      requestId
    )
}

function startProcessing(
  requestId: number,
): void {
  if (
    !processingIds.value
      .includes(requestId)
  ) {
    processingIds.value.push(
      requestId
    )
  }
}

function stopProcessing(
  requestId: number,
): void {
  processingIds.value =
    processingIds.value.filter(
      (id) =>
        id !== requestId
    )
}

function removeRequest(
  requestId: number,
): void {
  requests.value =
    requests.value.filter(
      (request) =>
        request.id !== requestId
    )
}

async function loadRequests(): Promise<void> {
  loading.value =
    true

  errorMessage.value =
    null

  requests.value =
    []

  currentPage.value =
    1

  hasMore.value =
    false

  try {
    const response =
      await getFollowRequests(1)

    requests.value =
      response.data.requests

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
      'Không thể tải yêu cầu theo dõi.'
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

  loadingMore.value =
    true

  try {
    const response =
      await getFollowRequests(
        currentPage.value + 1
      )

    requests.value.push(
      ...response.data.requests
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
    toastStore.error(
      'Không thể tải thêm yêu cầu theo dõi.'
    )
  } finally {
    loadingMore.value =
      false
  }
}

async function handleAccept(
  followRequest: FollowRequestItem,
): Promise<void> {
  if (
    isProcessing(
      followRequest.id
    )
  ) {
    return
  }

  startProcessing(
    followRequest.id
  )

  try {
    await acceptFollowRequest(
      followRequest.id
    )

    removeRequest(
      followRequest.id
    )

    toastStore.success(
      `Đã chấp nhận @${followRequest.requester.username}.`
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      removeRequest(
        followRequest.id
      )

      toastStore.error(
        'Yêu cầu này không còn tồn tại.'
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 403
    ) {
      toastStore.error(
        'Bạn không có quyền xử lý yêu cầu này.'
      )

      return
    }

    toastStore.error(
      'Không thể chấp nhận yêu cầu theo dõi.'
    )
  } finally {
    stopProcessing(
      followRequest.id
    )
  }
}

async function handleReject(
  followRequest: FollowRequestItem,
): Promise<void> {
  if (
    isProcessing(
      followRequest.id
    )
  ) {
    return
  }

  startProcessing(
    followRequest.id
  )

  try {
    await rejectFollowRequest(
      followRequest.id
    )

    removeRequest(
      followRequest.id
    )

    toastStore.success(
      `Đã từ chối @${followRequest.requester.username}.`
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      removeRequest(
        followRequest.id
      )

      toastStore.error(
        'Yêu cầu này không còn tồn tại.'
      )

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 403
    ) {
      toastStore.error(
        'Bạn không có quyền xử lý yêu cầu này.'
      )

      return
    }

    toastStore.error(
      'Không thể từ chối yêu cầu theo dõi.'
    )
  } finally {
    stopProcessing(
      followRequest.id
    )
  }
}

onMounted(() => {
  void loadRequests()
})
</script>

<template>
  <MainLayout>
    <section class="follow-requests-page">
      <header class="follow-requests-page__topbar">
        <strong>
          Yêu cầu theo dõi
        </strong>
      </header>

      <template v-if="loading">
        <div
          v-for="index in 5"
          :key="index"
          class="follow-requests-page__skeleton"
        >
          <AppSkeleton
            width="48px"
            height="48px"
            radius="50%"
          />

          <div class="follow-requests-page__skeleton-content">
            <AppSkeleton
              width="160px"
              height="16px"
            />

            <AppSkeleton
              width="110px"
              height="14px"
            />
          </div>
        </div>
      </template>

      <div
        v-else-if="
          errorMessage
          && requests.length === 0
        "
        class="follow-requests-page__state"
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
            loadRequests
          "
        >
          Thử lại
        </AppButton>
      </div>

      <div
        v-else-if="
          requests.length === 0
        "
        class="follow-requests-page__state"
      >
        <h2>
          Không có yêu cầu theo dõi
        </h2>

        <p>
          Các yêu cầu mới sẽ xuất hiện tại đây.
        </p>
      </div>

      <template v-else>
        <div class="follow-requests-page__list">
          <div
            v-for="followRequest in requests"
            :key="followRequest.id"
            class="follow-requests-page__item"
          >
            <RouterLink
              :to="
                `/@${followRequest.requester.username}`
              "
              class="follow-requests-page__user"
            >
              <div class="follow-requests-page__avatar">
                <img
                  v-if="
                    followRequest.requester.avatar_url
                  "
                  :src="
                    followRequest.requester.avatar_url
                  "
                  :alt="
                    followRequest.requester.display_name
                    || followRequest.requester.username
                  "
                />

                <span v-else>
                  {{
                    (
                      followRequest
                        .requester
                        .display_name
                      || followRequest
                        .requester
                        .username
                    )
                      .charAt(0)
                      .toUpperCase()
                  }}
                </span>
              </div>

              <div class="follow-requests-page__user-content">
                <div class="follow-requests-page__name">
                  <strong>
                    {{
                      followRequest
                        .requester
                        .display_name
                      || followRequest
                        .requester
                        .username
                    }}
                  </strong>

                  <span
                    v-if="
                      followRequest.requester.is_verified
                    "
                    aria-label="Tài khoản đã xác minh"
                  >
                    ✓
                  </span>
                </div>

                <div class="follow-requests-page__username">
                  @{{ followRequest.requester.username }}
                </div>

                <p
                  v-if="
                    followRequest.requester.bio
                  "
                  class="follow-requests-page__bio"
                >
                  {{ followRequest.requester.bio }}
                </p>
              </div>
            </RouterLink>

            <div class="follow-requests-page__actions">
              <AppButton
                :disabled="
                  isProcessing(
                    followRequest.id
                  )
                "
                @click="
                  handleAccept(
                    followRequest
                  )
                "
              >
                Chấp nhận
              </AppButton>

              <AppButton
                variant="secondary"
                :disabled="
                  isProcessing(
                    followRequest.id
                  )
                "
                @click="
                  handleReject(
                    followRequest
                  )
                "
              >
                Từ chối
              </AppButton>
            </div>
          </div>
        </div>

        <div
          v-if="hasMore"
          class="follow-requests-page__load-more"
        >
          <AppButton
            variant="secondary"
            :disabled="loadingMore"
            @click="
              loadMore
            "
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
  src="@/assets/styles/views/FollowRequestsView.scss"
></style>
