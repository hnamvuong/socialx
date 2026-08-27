<script setup lang="ts">
import {
  computed,
  nextTick,
  onBeforeUnmount,
  ref,
} from 'vue'
import axios from 'axios'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppButton from '@/components/ui/AppButton.vue'
import {
  createPost,
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

const emit = defineEmits<{
  created: [post: Post]
}>()

const authStore = useAuthStore()

const toastStore = useToastStore()

const content = ref('')

const selectedMedia = ref<SelectedMedia[]>([])

const fileInput = ref<HTMLInputElement | null>(null)

const submitting = ref(false)

const errorMessage = ref<string | null>(null)

let nextMediaId = 1

const maxCharacters = 280

const maxMedia = 4

const maxFileSize = 8 * 1024 * 1024

const textarea =
  ref<HTMLTextAreaElement | null>(
    null,
  )

const remainingCharacters =
  computed(() => {
    return (
      maxCharacters -
      content.value.length
    )
  })

const canSubmit =
  computed(() => {
    const hasContent =
      content.value.trim().length > 0

    const hasMedia =
      selectedMedia.value.length > 0

    return (
      !submitting.value &&
      remainingCharacters.value >= 0 &&
      (hasContent || hasMedia)
    )
  })

function openFilePicker(): void {
  fileInput.value?.click()
}

function revokePreview(
  previewUrl: string,
): void {
  URL.revokeObjectURL(
    previewUrl,
  )
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

  if (files.length === 0) {
    return
  }

  const remainingSlots =
    maxMedia - selectedMedia.value.length

  if (remainingSlots <= 0) {
    toastStore.error(
      'Bạn chỉ có thể chọn tối đa 4 ảnh.',
    )

    input.value = ''
    return
  }

  const acceptedFiles =
    files.slice(
      0,
      remainingSlots,
    )

  for (
    const file of acceptedFiles
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

  if (
    files.length >
    remainingSlots
  ) {
    toastStore.error(
      'Chỉ những ảnh trong giới hạn 4 ảnh được thêm.',
    )
  }

  input.value = ''
}

function removeMedia(
  id: number,
): void {
  const index =
    selectedMedia.value
      .findIndex(
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

async function resetComposer(): Promise<void> {
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

  await nextTick()

  resizeTextarea()
}

async function submit(): Promise<void> {
  if (!canSubmit.value) {
    return
  }

  submitting.value = true
  errorMessage.value = null

  try {
    const response =
      await createPost(
        content.value,
        selectedMedia.value.map(
          (item) => item.file,
        ),
      )

    emit(
      'created',
      response.data.post,
    )

    await resetComposer()

    toastStore.success(
      'Đã đăng bài.',
    )
  } catch (error: unknown) {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 422
    ) {
      const errors =
        error.response.data
          ?.errors

      errorMessage.value =
        errors?.content?.[0] ??
        errors?.media?.[0] ??
        'Dữ liệu bài viết không hợp lệ.'

      return
    }

    if (
      axios.isAxiosError(error) &&
      error.response?.status === 403
    ) {
      errorMessage.value =
        'Bạn không có quyền đăng bài.'

      return
    }

    errorMessage.value =
      'Không thể đăng bài. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}

function isDuplicateFile(
  file: File,
): boolean {
  return selectedMedia.value.some(
    (item) =>
      item.file.name ===
        file.name &&
      item.file.size ===
        file.size &&
      item.file.lastModified ===
        file.lastModified,
  )
}

function resizeTextarea(): void {
  const element =
    textarea.value

  if (!element) {
    return
  }

  element.style.height =
    'auto'

  element.style.height =
    `${element.scrollHeight}px`
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
  <section class="post-composer">
    <div class="post-composer__avatar">
      <AppAvatar
        :src="
          authStore.user?.avatar_url
        "
        :name="
          authStore.user
            ?.display_name ??
          authStore.user
            ?.name ??
          ''
        "
        :size="44"
      />
    </div>

    <div class="post-composer__main">
      <textarea
        v-model="content"
        class="post-composer__textarea"
        maxlength="280"
        placeholder="Chuyện gì đang xảy ra?"
        :disabled="submitting"
        @input="resizeTextarea"
      />

      <div
        v-if="
          selectedMedia.length > 0
        "
        class="post-composer__media-grid"
        :class="
          `post-composer__media-grid--${selectedMedia.length}`
        "
      >
        <div
          v-for="
            item in selectedMedia
          "
          :key="item.id"
          class="post-composer__media-item"
        >
          <img
            :src="item.previewUrl"
            :alt="item.file.name"
            class="post-composer__media-image"
          >

          <button
            type="button"
            class="post-composer__media-remove"
            aria-label="Xóa ảnh"
            :disabled="submitting"
            @click="
              removeMedia(item.id)
            "
          >
            ×
          </button>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="post-composer__error"
      >
        {{ errorMessage }}
      </p>

      <div class="post-composer__footer">
        <div class="post-composer__tools">
          <input
            ref="fileInput"
            type="file"
            accept="image/*"
            multiple
            class="post-composer__file-input"
            @change="handleFiles"
          >

          <AppButton
            variant="ghost"
            size="sm"
            :disabled="
              submitting ||
              selectedMedia.length >= 4
            "
            @click="openFilePicker"
          >
            Ảnh
          </AppButton>

          <span class="post-composer__media-count">
            {{ selectedMedia.length }}/4
          </span>
        </div>

        <div class="post-composer__submit-area">
          <span
            class="post-composer__counter"
            :class="{
              'post-composer__counter--warning':
                remainingCharacters <= 20 &&
                remainingCharacters >= 0,

              'post-composer__counter--danger':
                remainingCharacters < 0,
            }"
          >
            {{ remainingCharacters }}
          </span>

          <AppButton
            :loading="submitting"
            :disabled="!canSubmit"
            @click="submit"
          >
            Đăng
          </AppButton>
        </div>
      </div>
    </div>
  </section>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/post/PostComposer.scss"
></style>
