<script setup lang="ts">
import {
  onBeforeUnmount,
  ref,
  watch,
} from 'vue'

import axios from 'axios'

import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import QuotedPostCard from '@/components/post/QuotedPostCard.vue'

import {
  createPost,
} from '@/services/postService'

import {
  useToastStore,
} from '@/stores/toast'

import type {
  Post,
} from '@/types/post'

interface SelectedMedia {
  id: number
  file: File
  previewUrl: string
}

const props = defineProps<{
  open: boolean
  quotedPost: Post | null
}>()

const emit = defineEmits<{
  close: []
  created: [post: Post]
}>()

const toastStore =
  useToastStore()

const content =
  ref('')

const media =
  ref<SelectedMedia[]>([])

const fileInput =
  ref<HTMLInputElement | null>(
    null,
  )

const submitting =
  ref(false)

const errorMessage =
  ref<string | null>(null)

let nextMediaId = 1

const maxMedia = 4
const maxFileSize =
  8 * 1024 * 1024

function clearMedia(): void {
  for (const item of media.value) {
    URL.revokeObjectURL(
      item.previewUrl,
    )
  }

  media.value = []
}

function reset(): void {
  content.value = ''
  errorMessage.value = null

  clearMedia()
}

function close(): void {
  if (submitting.value) {
    return
  }

  emit('close')
}

function openFilePicker(): void {
  fileInput.value?.click()
}

function removeMedia(
  id: number,
): void {
  const index =
    media.value.findIndex(
      (item) =>
        item.id === id,
    )

  if (index === -1) {
    return
  }

  const [item] =
    media.value.splice(
      index,
      1,
    )

  if (item) {
    URL.revokeObjectURL(
      item.previewUrl,
    )
  }
}

function handleFiles(
  event: Event,
): void {
  const input =
    event.target as HTMLInputElement

  const files =
    Array.from(
      input.files ?? [],
    )

  const available =
    maxMedia - media.value.length

  for (
    const file of
      files.slice(0, available)
  ) {
    if (
      !file.type.startsWith(
        'image/',
      )
    ) {
      toastStore.error(
        `${file.name} không phải hình ảnh.`,
      )

      continue
    }

    if (
      file.size > maxFileSize
    ) {
      toastStore.error(
        `${file.name} vượt quá 8 MB.`,
      )

      continue
    }

    media.value.push({
      id: nextMediaId++,
      file,
      previewUrl:
        URL.createObjectURL(file),
    })
  }

  input.value = ''
}

async function submit(): Promise<void> {
  if (
    submitting.value
    || !props.quotedPost
  ) {
    return
  }

  const normalized =
    content.value.trim()

  if (
    !normalized
    && media.value.length === 0
  ) {
    errorMessage.value =
      'Hãy nhập nội dung hoặc thêm hình ảnh.'

    return
  }

  submitting.value = true
  errorMessage.value = null

  try {
    const response =
      await createPost(
        content.value,
        media.value.map(
          (item) =>
            item.file,
        ),
        {
          quotedPostId:
            props.quotedPost.id,
        },
      )

    emit(
      'created',
      response.data.post,
    )

    toastStore.success(
      'Đã đăng bài trích dẫn.',
    )

    reset()

    emit('close')
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 422
    ) {
      errorMessage.value =
        error.response.data
          ?.errors?.content?.[0]
        ?? error.response.data
          ?.errors?.quoted_post_id?.[0]
        ?? 'Bài trích dẫn không hợp lệ.'

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 404
    ) {
      errorMessage.value =
        'Bài viết được trích dẫn không còn tồn tại.'

      return
    }

    errorMessage.value =
      'Không thể đăng bài trích dẫn.'
  } finally {
    submitting.value = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (!open) {
      reset()
    }
  },
)

onBeforeUnmount(() => {
  clearMedia()
})
</script>

<template>
  <AppModal
    :open="open"
    title="Trích dẫn bài viết"
    @close="close"
  >
    <div class="quote-post-modal">
      <textarea
        v-model="content"
        rows="4"
        maxlength="280"
        class="quote-post-modal__textarea"
        placeholder="Thêm nhận xét..."
        :disabled="submitting"
      />

      <div
        v-if="media.length > 0"
        class="quote-post-modal__media"
      >
        <div
          v-for="item in media"
          :key="item.id"
          class="quote-post-modal__media-item"
        >
          <img
            :src="item.previewUrl"
            :alt="item.file.name"
          >

          <button
            type="button"
            class="quote-post-modal__remove"
            :disabled="submitting"
            @click="
              removeMedia(
                item.id
              )
            "
          >
            ×
          </button>
        </div>
      </div>

      <QuotedPostCard
        v-if="
          quotedPost
          && quotedPost.quoted_post === null
        "
        :post="{
          id: quotedPost.id,
          content: quotedPost.content,
          created_at: quotedPost.created_at,
          user: quotedPost.user,
          media: quotedPost.media,
        }"
      />

      <div
        v-else-if="quotedPost"
        class="quote-post-modal__quoted-preview"
      >
        <strong>
          @{{ quotedPost.user.username }}
        </strong>

        <p>
          {{ quotedPost.content }}
        </p>
      </div>

      <p
        v-if="errorMessage"
        class="quote-post-modal__error"
      >
        {{ errorMessage }}
      </p>

      <div class="quote-post-modal__footer">
        <div>
          <input
            ref="fileInput"
            type="file"
            accept="image/*"
            multiple
            class="quote-post-modal__file-input"
            @change="handleFiles"
          >

          <AppButton
            variant="ghost"
            size="sm"
            :disabled="
              submitting
              || media.length >= maxMedia
            "
            @click="openFilePicker"
          >
            Thêm ảnh
          </AppButton>
        </div>

        <AppButton
          :loading="submitting"
          @click="submit"
        >
          Đăng
        </AppButton>
      </div>
    </div>
  </AppModal>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/QuotePostModal.scss"
></style>
