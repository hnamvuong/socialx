<script setup lang="ts">
import {
  onBeforeUnmount,
  ref,
} from 'vue'
import axios from 'axios'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppButton from '@/components/ui/AppButton.vue'
import {
  uploadAvatar,
  uploadCover,
} from '@/services/userService'
import type {
  PublicUserProfile,
} from '@/types/user'

defineProps<{
  user: PublicUserProfile
}>()

const emit = defineEmits<{
  updated: [user: PublicUserProfile]
}>()

const avatarInput =
  ref<HTMLInputElement | null>(null)

const coverInput =
  ref<HTMLInputElement | null>(null)

const avatarPreview =
  ref<string | null>(null)

const coverPreview =
  ref<string | null>(null)

const selectedAvatar =
  ref<File | null>(null)

const selectedCover =
  ref<File | null>(null)

const avatarUploading =
  ref(false)

const coverUploading =
  ref(false)

const avatarError =
  ref<string | null>(null)

const coverError =
  ref<string | null>(null)

function revokePreview(
  value: string | null,
): void {
  if (
    value &&
    value.startsWith('blob:')
  ) {
    URL.revokeObjectURL(value)
  }
}

function selectAvatar(): void {
  avatarInput.value?.click()
}

function selectCover(): void {
  coverInput.value?.click()
}

function handleAvatarChange(
  event: Event,
): void {
  const input =
    event.target as HTMLInputElement

  const file =
    input.files?.[0]

  if (!file) {
    return
  }

  revokePreview(
    avatarPreview.value
  )

  selectedAvatar.value = file

  avatarPreview.value =
    URL.createObjectURL(file)

  avatarError.value = null
}

function handleCoverChange(
  event: Event,
): void {
  const input =
    event.target as HTMLInputElement

  const file =
    input.files?.[0]

  if (!file) {
    return
  }

  revokePreview(
    coverPreview.value
  )

  selectedCover.value = file

  coverPreview.value =
    URL.createObjectURL(file)

  coverError.value = null
}

async function saveAvatar(): Promise<void> {
  if (
    !selectedAvatar.value ||
    avatarUploading.value
  ) {
    return
  }

  avatarUploading.value = true
  avatarError.value = null

  try {
    const response =
      await uploadAvatar(
        selectedAvatar.value
      )

    emit(
      'updated',
      response.data.user
    )

    selectedAvatar.value = null

    revokePreview(
      avatarPreview.value
    )

    avatarPreview.value = null
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 422
    ) {
      avatarError.value =
        error.response.data
          ?.errors
          ?.avatar
          ?.[0] ??
        'Ảnh đại diện không hợp lệ.'

      return
    }

    avatarError.value =
      'Không thể tải ảnh đại diện.'
  } finally {
    avatarUploading.value = false
  }
}

async function saveCover(): Promise<void> {
  if (
    !selectedCover.value ||
    coverUploading.value
  ) {
    return
  }

  coverUploading.value = true
  coverError.value = null

  try {
    const response =
      await uploadCover(
        selectedCover.value
      )

    emit(
      'updated',
      response.data.user
    )

    selectedCover.value = null

    revokePreview(
      coverPreview.value
    )

    coverPreview.value = null
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 422
    ) {
      coverError.value =
        error.response.data
          ?.errors
          ?.cover
          ?.[0] ??
        'Ảnh bìa không hợp lệ.'

      return
    }

    coverError.value =
      'Không thể tải ảnh bìa.'
  } finally {
    coverUploading.value = false
  }
}

onBeforeUnmount(() => {
  revokePreview(
    avatarPreview.value
  )

  revokePreview(
    coverPreview.value
  )
})
</script>

<template>
  <section class="profile-media-editor">
    <div class="profile-media-editor__section">
      <div class="profile-media-editor__heading">
        Ảnh đại diện
      </div>

      <div class="profile-media-editor__avatar-row">
        <AppAvatar
          :src="
            avatarPreview ||
            user.avatar_url
          "
          :name="user.display_name"
          :size="88"
        />

        <div class="profile-media-editor__actions">
          <input
            ref="avatarInput"
            type="file"
            accept="image/*"
            class="profile-media-editor__input"
            @change="handleAvatarChange"
          >

          <AppButton
            variant="secondary"
            size="sm"
            :disabled="avatarUploading"
            @click="selectAvatar"
          >
            Chọn ảnh
          </AppButton>

          <AppButton
            v-if="selectedAvatar"
            size="sm"
            :loading="avatarUploading"
            @click="saveAvatar"
          >
            Cập nhật
          </AppButton>
        </div>
      </div>

      <p
        v-if="avatarError"
        class="profile-media-editor__error"
      >
        {{ avatarError }}
      </p>
    </div>

    <div class="profile-media-editor__section">
      <div class="profile-media-editor__heading">
        Ảnh bìa
      </div>

      <div
        class="profile-media-editor__cover"
        :style="{
          backgroundImage:
            coverPreview || user.cover_url
              ? `url(${coverPreview || user.cover_url})`
              : undefined,
        }"
      />

      <div class="profile-media-editor__actions">
        <input
          ref="coverInput"
          type="file"
          accept="image/*"
          class="profile-media-editor__input"
          @change="handleCoverChange"
        >

        <AppButton
          variant="secondary"
          size="sm"
          :disabled="coverUploading"
          @click="selectCover"
        >
          Chọn ảnh
        </AppButton>

        <AppButton
          v-if="selectedCover"
          size="sm"
          :loading="coverUploading"
          @click="saveCover"
        >
          Cập nhật
        </AppButton>
      </div>

      <p
        v-if="coverError"
        class="profile-media-editor__error"
      >
        {{ coverError }}
      </p>
    </div>
  </section>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/profile/ProfileMediaEditor.scss"
></style>
