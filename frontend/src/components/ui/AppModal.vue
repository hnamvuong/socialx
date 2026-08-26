<script setup lang="ts">
import {
  onBeforeUnmount,
  watch,
} from 'vue'

const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    closeOnBackdrop?: boolean
  }>(),
  {
    title: '',
    closeOnBackdrop: true,
  },
)

const emit = defineEmits<{
  close: []
}>()

function close(): void {
  emit('close')
}

function handleBackdrop(): void {
  if (props.closeOnBackdrop) {
    close()
  }
}

function handleKeydown(
  event: KeyboardEvent,
): void {
  if (
    props.open &&
    event.key === 'Escape'
  ) {
    close()
  }
}

watch(
  () => props.open,
  (open) => {
    document.body.style.overflow =
      open ? 'hidden' : ''
  },
)

window.addEventListener(
  'keydown',
  handleKeydown,
)

onBeforeUnmount(() => {
  window.removeEventListener(
    'keydown',
    handleKeydown,
  )

  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="app-modal"
      role="dialog"
      aria-modal="true"
    >
      <button
        type="button"
        class="app-modal__backdrop"
        aria-label="Đóng"
        @click="handleBackdrop"
      />

      <div class="app-modal__panel">
        <header class="app-modal__header">
          <h2 class="app-modal__title">
            {{ title }}
          </h2>

          <button
            type="button"
            class="app-modal__close"
            aria-label="Đóng"
            @click="close"
          >
            ×
          </button>
        </header>

        <div class="app-modal__body">
          <slot />
        </div>

        <footer
          v-if="$slots.footer"
          class="app-modal__footer"
        >
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/AppModal.scss"
></style>
