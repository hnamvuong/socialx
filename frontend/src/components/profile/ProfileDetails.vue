<script setup lang="ts">
import { computed } from 'vue'

import type {
  PublicUserProfile,
} from '@/types/user'

const props = defineProps<{
  user: PublicUserProfile
}>()

const joinedAt = computed(() => {
  return new Intl.DateTimeFormat(
    'vi-VN',
    {
      year: 'numeric',
      month: 'long',
    },
  ).format(
    new Date(props.user.created_at),
  )
})
</script>

<template>
  <section class="profile-details">
    <p
      v-if="user.bio"
      class="profile-details__bio"
    >
      {{ user.bio }}
    </p>

    <div class="profile-details__metadata">
      <span
        v-if="user.location"
        class="profile-details__metadata-item"
      >
        {{ user.location }}
      </span>

      <a
        v-if="user.website"
        :href="user.website"
        target="_blank"
        rel="noopener noreferrer"
        class="profile-details__website"
      >
        {{ user.website }}
      </a>

      <span class="profile-details__metadata-item">
        Tham gia {{ joinedAt }}
      </span>
    </div>

    <div
      v-if="user.is_private"
      class="profile-details__private"
    >
      Tài khoản này ở chế độ riêng tư.
    </div>
  </section>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/profile/ProfileDetails.scss"
></style>
