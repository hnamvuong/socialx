<script setup lang="ts">
import {
  reactive,
  watch,
} from 'vue'
import axios from 'axios'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import ProfileMediaEditor from '@/components/profile/ProfileMediaEditor.vue'
import {
  updateProfile,
} from '@/services/userService'
import type {
  PublicUserProfile,
  UpdateProfilePayload,
} from '@/types/user'

const props = defineProps<{
  open: boolean
  user: PublicUserProfile
}>()

const emit = defineEmits<{
  close: []
  updated: [user: PublicUserProfile]
  mediaUpdated: [user: PublicUserProfile]
}>()

const form = reactive({
  display_name: '',
  bio: '',
  location: '',
  website: '',
})

const errors = reactive<{
  display_name?: string
  bio?: string
  location?: string
  website?: string
}>({})

const state = reactive({
  submitting: false,
  generalError: '',
})

function resetErrors(): void {
  errors.display_name = undefined
  errors.bio = undefined
  errors.location = undefined
  errors.website = undefined

  state.generalError = ''
}

function fillForm(): void {
  form.display_name = props.user.display_name
  form.bio = props.user.bio ?? ''
  form.location = props.user.location ?? ''
  form.website = props.user.website ?? ''
}

function close(): void {
  if (state.submitting) {
    return
  }

  emit('close')
}

async function submit(): Promise<void> {
  if (state.submitting) {
    return
  }

  resetErrors()

  state.submitting = true

  const payload: UpdateProfilePayload = {
    display_name: form.display_name.trim(),
    bio: form.bio.trim() || null,
    location: form.location.trim() || null,
    website: form.website.trim() || null,
  }

  try {
    const response = await updateProfile(payload)

    emit(
      'updated',
      response.data.user,
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 422
    ) {
      const validationErrors = error.response.data?.errors
      errors.display_name = validationErrors?.display_name?.[0]
      errors.bio = validationErrors?.bio?.[0]
      errors.location = validationErrors?.location?.[0]
      errors.website = validationErrors?.website?.[0]

      return
    }

    state.generalError = 'Không thể cập nhật hồ sơ.'
  } finally {
    state.submitting = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (!open) {
      return
    }

    resetErrors()
    fillForm()
  },
)
</script>

<template>
  <AppModal
    :open="open"
    title="Chỉnh sửa hồ sơ"
    :close-on-backdrop="!state.submitting"
    @close="close"
  >
    <div class="edit-profile-modal">
      <ProfileMediaEditor
        :user="user"
        @updated="
          $emit(
            'mediaUpdated',
            $event
          )
        "
      />

      <form
        id="edit-profile-form"
        class="edit-profile"
        @submit.prevent="submit"
      >
        <div class="edit-profile__field">
          <label
            for="display-name"
            class="edit-profile__label"
          >
            Tên hiển thị
          </label>

          <input
            id="display-name"
            v-model="form.display_name"
            type="text"
            maxlength="50"
            class="edit-profile__input"
            :class="{
              'edit-profile__input--error':
                errors.display_name,
            }"
          >

          <div class="edit-profile__counter">
            {{ form.display_name.length }}/50
          </div>

          <p
            v-if="errors.display_name"
            class="edit-profile__error"
          >
            {{ errors.display_name }}
          </p>
        </div>

        <div class="edit-profile__field">
          <label
            for="profile-bio"
            class="edit-profile__label"
          >
            Tiểu sử
          </label>

          <textarea
            id="profile-bio"
            v-model="form.bio"
            maxlength="160"
            rows="4"
            class="edit-profile__textarea"
          />

          <div class="edit-profile__counter">
            {{ form.bio.length }}/160
          </div>

          <p
            v-if="errors.bio"
            class="edit-profile__error"
          >
            {{ errors.bio }}
          </p>
        </div>

        <div class="edit-profile__field">
          <label
            for="profile-location"
            class="edit-profile__label"
          >
            Vị trí
          </label>

          <input
            id="profile-location"
            v-model="form.location"
            type="text"
            maxlength="100"
            class="edit-profile__input"
          >

          <p
            v-if="errors.location"
            class="edit-profile__error"
          >
            {{ errors.location }}
          </p>
        </div>

        <div class="edit-profile__field">
          <label
            for="profile-website"
            class="edit-profile__label"
          >
            Website
          </label>

          <input
            id="profile-website"
            v-model="form.website"
            type="url"
            maxlength="255"
            placeholder="https://example.com"
            class="edit-profile__input"
          >

          <p
            v-if="errors.website"
            class="edit-profile__error"
          >
            {{ errors.website }}
          </p>
        </div>

        <p
          v-if="state.generalError"
          class="edit-profile__general-error"
        >
          {{ state.generalError }}
        </p>
      </form>
    </div>

    <template #footer>
      <AppButton
        variant="secondary"
        :disabled="state.submitting"
        @click="close"
      >
        Hủy
      </AppButton>

      <AppButton
        type="submit"
        form="edit-profile-form"
        :loading="state.submitting"
      >
        Lưu
      </AppButton>
    </template>
  </AppModal>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/profile/EditProfileModal.scss"
></style>
