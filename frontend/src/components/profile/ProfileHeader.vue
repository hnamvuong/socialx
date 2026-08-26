<script setup lang="ts">
import AppAvatar from '@/components/ui/AppAvatar.vue'

import type {
  PublicUserProfile,
} from '@/types/user'

defineProps<{
  user: PublicUserProfile
}>()
</script>

<template>
  <section class="profile-header">
    <div class="profile-header__cover" />

    <div class="profile-header__content">
      <div class="profile-header__avatar-wrapper">
        <AppAvatar
          :src="user.avatar_path"
          :name="user.display_name"
          :size="136"
        />
      </div>

      <div class="profile-header__identity">
        <div class="profile-header__name-row">
          <h1 class="profile-header__name">
            {{ user.display_name }}
          </h1>

          <span
            v-if="user.is_verified"
            class="profile-header__verified"
            title="Tài khoản đã xác minh"
            aria-label="Tài khoản đã xác minh"
          >
            ✓
          </span>
        </div>

        <div class="profile-header__username">
          @{{ user.username }}
        </div>
      </div>
    </div>
  </section>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/profile/ProfileHeader.scss"
></style>
