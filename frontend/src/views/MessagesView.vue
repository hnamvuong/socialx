<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

import { useRoute, useRouter } from 'vue-router'

import AppAvatar from '@/components/ui/AppAvatar.vue'

import MainLayout from '@/layouts/MainLayout.vue'

import { getConversations } from '@/services/conversationService'

import { getMessages } from '@/services/messageService'

import type { Conversation } from '@/types/conversation'

import type { Message } from '@/types/message'

const route = useRoute()
const router = useRouter()

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

const selectedConversationId = computed((): number | null => {
  const raw = route.query.conversation

  const value = Array.isArray(raw) ? raw[0] : raw

  if (!value) {
    return null
  }

  const id = Number(value)

  return Number.isInteger(id) && id > 0 ? id : null
})

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
  } catch {
    conversations.value = []

    conversationError.value = 'Không thể tải danh sách cuộc trò chuyện.'
  } finally {
    loadingConversations.value = false
  }
}

async function selectConversation(conversation: Conversation): Promise<void> {
  selectedConversation.value = conversation

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

onMounted(() => {
  void initializePage()
})
</script>

<template>
  <MainLayout>
    <section class="messages-page">
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

          <time
            v-if="conversation.last_message"
            class="conversation-item__time"
            :datetime="conversation.last_message.created_at"
          >
            {{ formatTime(conversation.last_message.created_at) }}
          </time>
        </button>
      </aside>

      <section class="messages-page__conversation">
        <template v-if="selectedConversation">
          <header class="chat-header">
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

          <div v-else class="message-history">
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

            <article v-for="message in messages" :key="message.id" class="message-row">
              <AppAvatar
                :src="message.sender.avatar_url"
                :name="message.sender.display_name ?? message.sender.username"
                :size="32"
              />

              <div class="message-row__body">
                <strong>
                  {{ message.sender.display_name ?? message.sender.username }}
                </strong>

                <p v-if="message.body">
                  {{ message.body }}
                </p>

                <div v-if="message.attachments.length > 0" class="message-row__attachments">
                  <img
                    v-for="attachment in message.attachments"
                    :key="attachment.id"
                    :src="attachment.url"
                    alt=""
                    class="message-row__image"
                  />
                </div>

                <time :datetime="message.created_at">
                  {{ formatTime(message.created_at) }}
                </time>
              </div>
            </article>
          </div>
        </template>

        <div v-else class="messages-page__empty-chat">Chọn một cuộc trò chuyện.</div>
      </section>
    </section>
  </MainLayout>
</template>

<style lang="scss" src="@/assets/styles/views/MessagesView.scss"></style>
