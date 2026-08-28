<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  ref,
} from 'vue'
import axios from 'axios'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppButton from '@/components/ui/AppButton.vue'
import {
  createReply,
} from '@/services/postService'
import {
  useAuthStore,
} from '@/stores/auth'
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
  parentPost: Post
}>()

const emit = defineEmits<{
  created: [post: Post]
}>()

const authStore = useAuthStore()

const toastStore = useToastStore()

const content = ref('')

const selectedMedia = ref<SelectedMedia[]>([])

const fileInput =
  ref<HTMLInputElement | null>(
    null,
  )

const submitting = ref(false)

const errorMessage = ref<string | null>(null)

const maxCharacters = 280

const maxMedia = 4

const maxFileSize = 8 * 1024 * 1024

let nextMediaId = 1

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

    const hasMedia = selectedMedia.value.length > 0

    return (
      !submitting.value
      && remainingCharacters.value >= 0
      && (
        hasContent
        || hasMedia
      )
    )
  })

function openFilePicker(): void {
  fileInput.value?.click()
}

function revokePreview(
  url: string,
): void {
  URL.revokeObjectURL(url)
}

function isDuplicateFile(
  file: File,
): boolean {
  return selectedMedia.value.some(
    (item) =>
      item.file.name === file.name
      && item.file.size === file.size
      && item.file.lastModified === file.lastModified,
  )
}

function handleFiles(
  event: Event,
): void {
  const input = event.target as HTMLInputElement

  const files =
    Array.from(
      input.files ?? [],
    )

  const remainingSlots =
    maxMedia
    - selectedMedia.value.length

  if (remainingSlots <= 0) {
    toastStore.error(
      'Bạn chỉ có thể chọn tối đa 4 ảnh.',
    )

    input.value = ''
    return
  }

  const accepted =
    files.slice(
      0,
      remainingSlots,
    )

  for (const file of accepted) {
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
      file.size >
      maxFileSize
    ) {
      toastStore.error(
        `${file.name} vượt quá 8 MB.`,
      )

      continue
    }

    if (
      isDuplicateFile(file)
    ) {
      toastStore.error(
        `${file.name} đã được chọn.`,
      )

      continue
    }

    selectedMedia.value.push({
      id: nextMediaId++,
      file,
      previewUrl:
        URL.createObjectURL(file),
    })
  }

  input.value = ''
}

function removeMedia(
  id: number,
): void {
  const index =
    selectedMedia.value.findIndex(
      (item) =>
        item.id === id,
    )

  if (index === -1) {
    return
  }

  const [removed] =
    selectedMedia.value.splice(
      index,
      1,
    )

  if (removed) {
    revokePreview(
      removed.previewUrl,
    )
  }
}

function reset(): void {
  content.value = ''

  for (
    const item of
      selectedMedia.value
  ) {
    revokePreview(
      item.previewUrl,
    )
  }

  selectedMedia.value = []

  errorMessage.value = null
}

async function submit(): Promise<void> {
  if (!canSubmit.value) {
    return
  }

  submitting.value = true
  errorMessage.value = null

  try {
    const response =
      await createReply(
        props.parentPost.id,
        content.value,
        selectedMedia.value.map(
          (item) =>
            item.file,
        ),
      )

    emit(
      'created',
      response.data.post,
    )

    reset()

    toastStore.success(
      'Đã đăng phản hồi.',
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error)
      && error.response?.status === 422
    ) {
      const errors =
        error.response.data
          ?.errors

      errorMessage.value =
        errors?.content?.[0]
        ?? errors?.media?.[0]
        ?? 'Phản hồi không hợp lệ.'

      return
    }

    if (
      axios.isAxiosError(error)
      && error.response?.status === 403
    ) {
      errorMessage.value =
        'Bạn không có quyền phản hồi.'

      return
    }

    errorMessage.value =
      'Không thể đăng phản hồi.'
  } finally {
    submitting.value = false
  }
}

onBeforeUnmount(() => {
  for (
    const item of
      selectedMedia.value
  ) {
    revokePreview(
      item.previewUrl,
    )
  }
})
</script>

<template>
  <section class="reply-composer">
    <div class="reply-composer__context">
      Phản hồi
      <strong>
        @{{ parentPost.user.username }}
      </strong>
    </div>

    <div class="reply-composer__body">
      <AppAvatar
        :src="
          authStore.user?.avatar_url
        "
        :name="
          authStore.user
            ?.display_name
          ?? authStore.user
            ?.name
          ?? ''
        "
        :size="40"
      />

      <div class="reply-composer__main">
        <textarea
          v-model="content"
          maxlength="280"
          rows="3"
          class="reply-composer__textarea"
          placeholder="Đăng phản hồi của bạn"
          :disabled="submitting"
        />

        <div
          v-if="
            selectedMedia.length > 0
          "
          class="reply-composer__media"
        >
          <div
            v-for="
              item in selectedMedia
            "
            :key="item.id"
            class="reply-composer__media-item"
          >
            <img
              :src="item.previewUrl"
              :alt="item.file.name"
              class="reply-composer__media-image"
            >

            <button
              type="button"
              class="reply-composer__remove"
              aria-label="Xóa ảnh"
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

        <p
          v-if="errorMessage"
          class="reply-composer__error"
        >
          {{ errorMessage }}
        </p>

        <div class="reply-composer__footer">
          <div class="reply-composer__tools">
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              multiple
              class="reply-composer__file-input"
              @change="handleFiles"
            >

            <AppButton
              variant="ghost"
              size="sm"
              :disabled="
                submitting
                || selectedMedia.length
                  >= maxMedia
              "
              @click="openFilePicker"
            >
              Ảnh
            </AppButton>

            <span class="reply-composer__media-count">
              {{ selectedMedia.length }}/4
            </span>
          </div>

          <div class="reply-composer__submit">
            <span
              class="reply-composer__counter"
              :class="{
                'reply-composer__counter--warning':
                  remainingCharacters <= 20,
              }"
            >
              {{ remainingCharacters }}
            </span>

            <AppButton
              size="sm"
              :loading="submitting"
              :disabled="!canSubmit"
              @click="submit"
            >
              Phản hồi
            </AppButton>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/ReplyComposer.scss"
></style>
