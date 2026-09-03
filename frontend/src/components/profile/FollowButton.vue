<script setup lang="ts">
import {
  computed,
} from 'vue'

import AppButton from '@/components/ui/AppButton.vue'

import type {
  FollowRelationship,
} from '@/types/user'

const props = defineProps<{
  relationship: FollowRelationship
  loading: boolean
}>()

const emit = defineEmits<{
  toggle: []
}>()

const label =
  computed((): string => {
    if (props.loading) {
      return 'Đang xử lý...'
    }

    switch (
      props.relationship
    ) {
      case 'following':
        return 'Đang theo dõi'

      case 'requested':
        return 'Đã gửi yêu cầu'

      case 'none':
        return 'Theo dõi'

      default:
        return ''
    }
  })

const variant =
  computed(() => {
    return props.relationship === 'none'
      ? 'primary'
      : 'secondary'
  })
</script>

<template>
  <div class="follow-button">
    <AppButton
      :variant="variant"
      :disabled="loading"
      @click="
        emit('toggle')
      "
    >
      {{ label }}
    </AppButton>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/profile/FollowButton.scss"
></style>
