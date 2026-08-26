<script setup lang="ts">
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'

withDefaults(
  defineProps<{
    open: boolean
    title?: string
    message: string
    confirmText?: string
    cancelText?: string
    loading?: boolean
    danger?: boolean
  }>(),
  {
    title: 'Xác nhận',
    confirmText: 'Xác nhận',
    cancelText: 'Hủy',
    loading: false,
    danger: false,
  },
)

defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <AppModal
    :open="open"
    :title="title"
    @close="$emit('cancel')"
  >
    <p class="confirm-dialog__message">
      {{ message }}
    </p>

    <template #footer>
      <AppButton
        variant="secondary"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        {{ cancelText }}
      </AppButton>

      <AppButton
        :variant="
          danger
            ? 'danger'
            : 'primary'
        "
        :loading="loading"
        @click="$emit('confirm')"
      >
        {{ confirmText }}
      </AppButton>
    </template>
  </AppModal>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/ConfirmDialog.scss"
></style>
