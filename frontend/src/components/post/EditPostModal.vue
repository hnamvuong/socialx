<script setup lang="ts">
import {
  computed,
  ref,
  watch,
} from 'vue'
import axios from 'axios'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import {
  updatePost,
} from '@/services/postService'

import type {
  Post,
} from '@/types/post'

const props = defineProps<{
  open: boolean
  post: Post
}>()

const emit = defineEmits<{
  close: []
  updated: [post: Post]
}>()

const content = ref('')

const submitting = ref(false)

const errorMessage = ref<string | null>(null)

const maxCharacters = 280

const remainingCharacters =
  computed(() => {
    return (
      maxCharacters -
      content.value.length
    )
  })

const canSubmit =
  computed(() => {
    const hasContent = content.value.trim().length > 0

    const hasExistingMedia = props.post.media.length > 0

    return (
      !submitting.value
      && remainingCharacters.value >= 0
      && (
        hasContent
        || hasExistingMedia
      )
    )
  })

function close(): void {
  if (submitting.value) {
    return
  }

  emit('close')
}

async function submit(): Promise<void> {
  if (!canSubmit.value) {
    return
  }

  submitting.value = true
  errorMessage.value = null

  try {
    const response =
      await updatePost(
        props.post.id,
        content.value,
      )

    emit(
      'updated',
      response.data.post,
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 422
    ) {
      errorMessage.value =
        error.response.data
          ?.errors
          ?.content
          ?.[0]
        ?? 'Nội dung bài viết không hợp lệ.'

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 403
    ) {
      errorMessage.value =
        'Bạn không có quyền chỉnh sửa bài viết này.'

      return
    }

    errorMessage.value =
      'Không thể cập nhật bài viết.'
  } finally {
    submitting.value = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (!open) {
      return
    }

    content.value = props.post.content ?? ''

    errorMessage.value = null
  },
)
</script>

<template>
  <AppModal
    :open="open"
    title="Chỉnh sửa bài viết"
    :close-on-backdrop="!submitting"
    @close="close"
  >
    <form
      id="edit-post-form"
      class="edit-post"
      @submit.prevent="submit"
    >
      <textarea
        v-model="content"
        class="edit-post__textarea"
        maxlength="280"
        rows="6"
        placeholder="Nội dung bài viết"
        :disabled="submitting"
      />

      <div class="edit-post__meta">
        <span
          v-if="post.media.length > 0"
          class="edit-post__media-note"
        >
          {{ post.media.length }} ảnh hiện có sẽ được giữ nguyên.
        </span>

        <span
          class="edit-post__counter"
          :class="{
            'edit-post__counter--warning':
              remainingCharacters <= 20,

            'edit-post__counter--danger':
              remainingCharacters < 0,
          }"
        >
          {{ remainingCharacters }}
        </span>
      </div>

      <p
        v-if="errorMessage"
        class="edit-post__error"
      >
        {{ errorMessage }}
      </p>
    </form>

    <template #footer>
      <AppButton
        variant="secondary"
        :disabled="submitting"
        @click="close"
      >
        Hủy
      </AppButton>

      <AppButton
        type="submit"
        form="edit-post-form"
        :loading="submitting"
        :disabled="!canSubmit"
      >
        Lưu
      </AppButton>
    </template>
  </AppModal>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/EditPostModal.scss"
></style>
