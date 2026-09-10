<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { useRoute, useRouter } from 'vue-router'

import AppAvatar from '@/components/ui/AppAvatar.vue'

import MainLayout from '@/layouts/MainLayout.vue'

import { getConversations } from '@/services/conversationService'

import echo from '@/services/echo'

import { getMessages, sendMessage } from '@/services/messageService'

import { useAuthStore } from '@/stores/auth'

import type { Conversation } from '@/types/conversation'

import type { Message } from '@/types/message'

interface RealtimeMessageSent {
  message_id: number
  conversation_id: number
  sender_id: number
}

const route = useRoute()

const router = useRouter()

const authStore = useAuthStore()

const conversations = ref<Conversation[]>([])

const selectedConversation = ref<Conversation | null>(null)

const messages = ref<Message[]>([])

const loadingConversations = ref(false)

const loadingMessages = ref(false)

const loadingOlder = ref(false)

const conversationError = ref<string | null>(null)

const messageError = ref<string | null>(null)

const nextCursor = ref<string | null>(null)

const hasMore = ref(false)

const composerBody = ref('')

const selectedImages = ref<File[]>([])

const imagePreviews = ref<string[]>([])

const sendingMessage = ref(false)

const sendError = ref<string | null>(null)

const fileInput = ref<HTMLInputElement | null>(null)

const messageHistoryElement = ref<HTMLElement | null>(null)

const realtimeConversationIds = new Set<number>()

const realtimeUnreadConversationIds = ref<number[]>([])

let realtimeInboxUserId: number | null = null

const selectedConversationId = computed((): number | null => {
  const raw = route.query.conversation

  const value = Array.isArray(raw) ? raw[0] : raw

  if (!value) {
    return null
  }

  const id = Number(value)

  return Number.isInteger(id) && id > 0 ? id : null
})

const canSend = computed((): boolean => {
  if (sendingMessage.value) {
    return false
  }

  return composerBody.value.trim().length > 0 || selectedImages.value.length > 0
})

const hasSelectedConversation = computed((): boolean => selectedConversation.value !== null)

function memberName(conversation: Conversation): string {
  const member = conversation.other_member

  if (!member) {
    return conversation.title ?? 'Cuộc trò chuyện'
  }

  return member.display_name ?? member.username
}

function conversationPreview(conversation: Conversation): string {
  const lastMessage = conversation.last_message

  if (!lastMessage) {
    return 'Chưa có tin nhắn.'
  }

  if (lastMessage.body) {
    return lastMessage.body
  }

  if (lastMessage.has_attachments) {
    return 'Đã gửi một hình ảnh.'
  }

  return 'Tin nhắn mới.'
}

function formatTime(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return ''
  }

  return new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

async function loadConversations(): Promise<void> {
  loadingConversations.value = true

  conversationError.value = null

  try {
    conversations.value = await getConversations()

    syncRealtimeSubscriptions()
  } catch {
    conversations.value = []

    conversationError.value = 'Không thể tải danh sách cuộc trò chuyện.'
  } finally {
    loadingConversations.value = false
  }
}

async function selectConversation(conversation: Conversation): Promise<void> {
  if (selectedConversation.value?.id !== conversation.id) {
    resetComposer()
  }

  selectedConversation.value = conversation

  clearRealtimeUnread(conversation.id)

  await router.replace({
    path: '/messages',

    query: {
      conversation: String(conversation.id),
    },
  })

  await loadMessages(conversation.id)
}

async function loadMessages(conversationId: number): Promise<void> {
  loadingMessages.value = true

  messageError.value = null

  try {
    const response = await getMessages(conversationId)

    /*
     * Backend trả newest-first.
     * UI hiển thị oldest → newest.
     */
    messages.value = [...response.messages].reverse()

    nextCursor.value = response.pagination.next_cursor

    hasMore.value = response.pagination.has_more

    await scrollToBottom()
  } catch {
    messages.value = []

    messageError.value = 'Không thể tải tin nhắn.'
  } finally {
    loadingMessages.value = false
  }
}

async function loadOlderMessages(): Promise<void> {
  if (!selectedConversation.value || !nextCursor.value || loadingOlder.value) {
    return
  }

  loadingOlder.value = true

  try {
    const response = await getMessages(
      selectedConversation.value.id,

      nextCursor.value,
    )

    const olderMessages = [...response.messages].reverse()

    messages.value.unshift(...olderMessages)

    nextCursor.value = response.pagination.next_cursor

    hasMore.value = response.pagination.has_more
  } catch {
    messageError.value = 'Không thể tải tin nhắn cũ hơn.'
  } finally {
    loadingOlder.value = false
  }
}

async function initializePage(): Promise<void> {
  await loadConversations()

  if (conversations.value.length === 0) {
    return
  }

  const requestedId = selectedConversationId.value

  const requestedConversation = requestedId
    ? conversations.value.find((conversation) => conversation.id === requestedId)
    : null

  const conversation = requestedConversation ?? conversations.value[0]

  if (!conversation) {
    return
  }

  await selectConversation(conversation)
}

function isMyMessage(message: Message): boolean {
  return authStore.user?.id === message.sender.id
}

function openImagePicker(): void {
  fileInput.value?.click()
}

function revokeImagePreviews(): void {
  for (const preview of imagePreviews.value) {
    URL.revokeObjectURL(preview)
  }

  imagePreviews.value = []
}

function handleImageSelection(event: Event): void {
  const input = event.target as HTMLInputElement

  const files = Array.from(input.files ?? [])

  if (files.length === 0) {
    return
  }

  const availableSlots = 4 - selectedImages.value.length

  const acceptedFiles = files
    .filter((file) => file.type.startsWith('image/'))
    .slice(0, availableSlots)

  for (const file of acceptedFiles) {
    selectedImages.value.push(file)

    imagePreviews.value.push(URL.createObjectURL(file))
  }

  /*
   * Cho phép chọn lại chính file
   * vừa chọn sau khi remove.
   */
  input.value = ''
}

function removeSelectedImage(index: number): void {
  const preview = imagePreviews.value[index]

  if (preview) {
    URL.revokeObjectURL(preview)
  }

  selectedImages.value.splice(index, 1)

  imagePreviews.value.splice(index, 1)
}

function resetComposer(): void {
  composerBody.value = ''

  revokeImagePreviews()

  selectedImages.value = []

  sendError.value = null

  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function scrollToBottom(): Promise<void> {
  await nextTick()

  requestAnimationFrame(() => {
    const element = messageHistoryElement.value

    if (!element) {
      return
    }

    element.scrollTop = element.scrollHeight
  })
}

async function handleSendMessage(): Promise<void> {
  const conversation = selectedConversation.value

  if (!conversation || !canSend.value) {
    return
  }

  sendingMessage.value = true

  sendError.value = null

  try {
    const formData = new FormData()

    const body = composerBody.value.trim()

    if (body) {
      formData.append('body', body)
    }

    for (const image of selectedImages.value) {
      formData.append('attachments[]', image)
    }

    const message = await sendMessage(conversation.id, formData)

    appendMessageIfMissing(message)

    updateConversationAfterSend(conversation, message)

    resetComposer()

    await scrollToBottom()
  } catch {
    sendError.value = 'Không thể gửi tin nhắn.'
  } finally {
    sendingMessage.value = false
  }
}

function updateConversationAfterSend(conversation: Conversation, message: Message): void {
  conversation.last_message = {
    id: message.id,

    body: message.body,

    sender_id: message.sender.id,

    has_attachments: message.attachments.length > 0,

    created_at: message.created_at,
  }

  conversation.updated_at = message.created_at

  const index = conversations.value.findIndex((item) => item.id === conversation.id)

  if (index <= 0) {
    return
  }

  const [activeConversation] = conversations.value.splice(index, 1)

  if (activeConversation) {
    conversations.value.unshift(activeConversation)
  }
}

function handleComposerKeydown(event: KeyboardEvent): void {
  if (event.key !== 'Enter' || event.shiftKey) {
    return
  }

  event.preventDefault()

  void handleSendMessage()
}

async function closeConversation(): Promise<void> {
  selectedConversation.value = null

  messages.value = []

  nextCursor.value = null

  hasMore.value = false

  resetComposer()

  await router.replace({
    path: '/messages',
  })
}

function subscribeToConversation(conversationId: number): void {
  if (realtimeConversationIds.has(conversationId)) {
    return
  }

  realtimeConversationIds.add(conversationId)

  echo
    .private(`conversations.${conversationId}`)
    .listen('.message.sent', (event: RealtimeMessageSent) => {
      void handleRealtimeMessage(event)
    })
}

function syncRealtimeSubscriptions(): void {
  const currentIds = new Set(conversations.value.map((conversation) => conversation.id))

  for (const conversationId of realtimeConversationIds) {
    if (currentIds.has(conversationId)) {
      continue
    }

    echo.leave(`conversations.${conversationId}`)

    realtimeConversationIds.delete(conversationId)
  }

  for (const conversation of conversations.value) {
    subscribeToConversation(conversation.id)
  }
}

function stopRealtime(): void {
  for (const conversationId of realtimeConversationIds) {
    echo.leave(`conversations.${conversationId}`)
  }

  realtimeConversationIds.clear()
}

function appendMessageIfMissing(message: Message): boolean {
  const exists = messages.value.some((current) => current.id === message.id)

  if (exists) {
    return false
  }

  messages.value.push(message)

  return true
}

async function handleRealtimeMessage(event: RealtimeMessageSent): Promise<void> {
  const activeConversationId = selectedConversation.value?.id

  if (activeConversationId !== event.conversation_id) {
    return
  }

  await refreshActiveConversation(event)
}

async function refreshActiveConversation(event: RealtimeMessageSent): Promise<void> {
  try {
    const response = await getMessages(event.conversation_id)

    const incomingMessage = response.messages.find((message) => message.id === event.message_id)

    if (!incomingMessage) {
      return
    }

    const appended = appendMessageIfMissing(incomingMessage)

    if (!appended) {
      return
    }

    await scrollToBottom()
  } catch {
    /*
     * WebSocket refresh lỗi không
     * phá history hiện tại.
     */
  }
}

async function refreshConversationList(): Promise<void> {
  try {
    const refreshed = await getConversations()

    const selectedId = selectedConversation.value?.id

    conversations.value = refreshed

    if (selectedId) {
      const refreshedSelected = refreshed.find((conversation) => conversation.id === selectedId)

      if (refreshedSelected) {
        selectedConversation.value = refreshedSelected
      }
    }

    syncRealtimeSubscriptions()
  } catch {
    /*
     * Realtime refresh lỗi không
     * xóa conversation list hiện tại.
     */
  }
}

function hasRealtimeUnread(conversationId: number): boolean {
  return realtimeUnreadConversationIds.value.includes(conversationId)
}

function markRealtimeUnread(conversationId: number): void {
  if (hasRealtimeUnread(conversationId)) {
    return
  }

  realtimeUnreadConversationIds.value.push(conversationId)
}

function clearRealtimeUnread(conversationId: number): void {
  realtimeUnreadConversationIds.value = realtimeUnreadConversationIds.value.filter(
    (id) => id !== conversationId,
  )
}

function startInboxRealtime(userId: number): void {
  if (realtimeInboxUserId === userId) {
    return
  }

  if (realtimeInboxUserId !== null) {
    echo.leave(`inbox.${realtimeInboxUserId}`)
  }

  realtimeInboxUserId = userId

  echo.private(`inbox.${userId}`).listen('.message.sent', (event: RealtimeMessageSent) => {
    void handleInboxRealtimeMessage(event)
  })
}

function stopInboxRealtime(): void {
  if (realtimeInboxUserId === null) {
    return
  }

  echo.leave(`inbox.${realtimeInboxUserId}`)

  realtimeInboxUserId = null
}

async function handleInboxRealtimeMessage(event: RealtimeMessageSent): Promise<void> {
  const activeConversationId = selectedConversation.value?.id

  if (activeConversationId !== event.conversation_id) {
    markRealtimeUnread(event.conversation_id)
  }

  await refreshConversationList()
}

onMounted(() => {
  void initializePage()
})

watch(
  () => authStore.user?.id,

  (userId) => {
    if (!userId) {
      stopInboxRealtime()

      return
    }

    startInboxRealtime(userId)
  },

  {
    immediate: true,
  },
)

onBeforeUnmount(() => {
  stopRealtime()

  stopInboxRealtime()

  revokeImagePreviews()
})
</script>

<template>
  <MainLayout :hide-right-sidebar="true">
    <section
      class="messages-page"
      :class="{
        'messages-page--conversation-open': hasSelectedConversation,
      }"
    >
      <aside class="messages-page__conversations">
        <header class="messages-page__header">
          <h1>Tin nhắn</h1>
        </header>

        <div v-if="loadingConversations" class="messages-page__state">
          Đang tải cuộc trò chuyện...
        </div>

        <div v-else-if="conversationError" class="messages-page__state">
          {{ conversationError }}
        </div>

        <div v-else-if="conversations.length === 0" class="messages-page__state">
          Chưa có cuộc trò chuyện.
        </div>

        <button
          v-for="conversation in conversations"
          :key="conversation.id"
          type="button"
          class="conversation-item"
          :class="{
            'conversation-item--active': selectedConversation?.id === conversation.id,
            'conversation-item--unread': hasRealtimeUnread(conversation.id),
          }"
          @click="selectConversation(conversation)"
        >
          <AppAvatar
            :src="conversation.other_member?.avatar_url ?? null"
            :name="memberName(conversation)"
            :size="44"
          />

          <span class="conversation-item__body">
            <strong class="conversation-item__name">
              {{ memberName(conversation) }}
            </strong>

            <span class="conversation-item__preview">
              {{ conversationPreview(conversation) }}
            </span>
          </span>

          <span class="conversation-item__meta">
            <time
              v-if="conversation.last_message"
              class="conversation-item__time"
              :datetime="conversation.last_message.created_at"
            >
              {{ formatTime(conversation.last_message.created_at) }}
            </time>

            <span
              v-if="hasRealtimeUnread(conversation.id)"
              class="conversation-item__unread-dot"
              aria-label="
      Có tin nhắn mới
    "
            />
          </span>
        </button>
      </aside>

      <section class="messages-page__conversation">
        <template v-if="selectedConversation">
          <header class="chat-header">
            <button
              type="button"
              class="chat-header__back"
              aria-label="
                Quay lại danh sách tin nhắn
              "
              @click="closeConversation"
            >
              ←
            </button>

            <AppAvatar
              :src="selectedConversation.other_member?.avatar_url ?? null"
              :name="memberName(selectedConversation)"
              :size="40"
            />

            <strong>
              {{ memberName(selectedConversation) }}
            </strong>
          </header>

          <div v-if="loadingMessages" class="messages-page__state">Đang tải tin nhắn...</div>

          <div
            v-else
            ref="
              messageHistoryElement
            "
            class="message-history"
          >
            <button
              v-if="hasMore"
              type="button"
              class="message-history__older"
              :disabled="loadingOlder"
              @click="loadOlderMessages"
            >
              {{ loadingOlder ? 'Đang tải...' : 'Tải tin nhắn cũ hơn' }}
            </button>

            <div v-if="messageError" class="messages-page__error">
              {{ messageError }}
            </div>

            <div v-if="messages.length === 0" class="messages-page__state">Chưa có tin nhắn.</div>

            <div v-else class="message-history__messages">
              <article
                v-for="message in messages"
                :key="message.id"
                class="message-row"
                :class="{
                  'message-row--mine': isMyMessage(message),

                  'message-row--theirs': !isMyMessage(message),
                }"
              >
                <AppAvatar
                  v-if="!isMyMessage(message)"
                  :src="message.sender.avatar_url"
                  :name="message.sender.display_name ?? message.sender.username"
                  :size="32"
                  class="message-row__avatar"
                />

                <div class="message-row__content">
                  <span v-if="!isMyMessage(message)" class="message-row__sender">
                    {{ message.sender.display_name ?? message.sender.username }}
                  </span>

                  <div class="message-bubble">
                    <p v-if="message.body" class="message-bubble__text">
                      {{ message.body }}
                    </p>

                    <div v-if="message.attachments.length > 0" class="message-bubble__attachments">
                      <img
                        v-for="attachment in message.attachments"
                        :key="attachment.id"
                        :src="attachment.url"
                        alt=""
                        class="message-bubble__image"
                        @load="scrollToBottom"
                      />
                    </div>
                  </div>

                  <time class="message-row__time" :datetime="message.created_at">
                    {{ formatTime(message.created_at) }}
                  </time>
                </div>
              </article>
            </div>
          </div>

          <form class="chat-composer" @submit.prevent="handleSendMessage">
            <div v-if="imagePreviews.length > 0" class="chat-composer__previews">
              <div
                v-for="(preview, index) in imagePreviews"
                :key="preview"
                class="chat-composer__preview"
              >
                <img :src="preview" alt="" />

                <button
                  type="button"
                  class="chat-composer__remove-image"
                  aria-label="
                    Xóa ảnh
                  "
                  @click="removeSelectedImage(index)"
                >
                  ×
                </button>
              </div>
            </div>

            <div v-if="sendError" class="chat-composer__error">
              {{ sendError }}
            </div>

            <div class="chat-composer__controls">
              <input
                ref="fileInput"
                type="file"
                accept="
                  image/jpeg,
                  image/png,
                  image/webp
                "
                multiple
                class="chat-composer__file-input"
                @change="handleImageSelection"
              />

              <button
                type="button"
                class="chat-composer__media-button"
                :disabled="selectedImages.length >= 4 || sendingMessage"
                aria-label="
                  Thêm ảnh
                "
                @click="openImagePicker"
              >
                +
              </button>

              <textarea
                v-model="composerBody"
                class="chat-composer__textarea"
                maxlength="5000"
                rows="1"
                placeholder="Nhập tin nhắn..."
                :disabled="sendingMessage"
                @keydown="handleComposerKeydown"
              />

              <button type="submit" class="chat-composer__send" :disabled="!canSend">
                {{ sendingMessage ? 'Đang gửi...' : 'Gửi' }}
              </button>
            </div>
          </form>
        </template>

        <div v-else class="messages-page__empty-chat">Chọn một cuộc trò chuyện.</div>
      </section>
    </section>
  </MainLayout>
</template>

<style lang="scss" src="@/assets/styles/views/MessagesView.scss"></style>
