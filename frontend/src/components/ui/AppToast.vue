<script setup lang="ts">
import { useToastStore } from '@/stores/toast'

const toastStore =
  useToastStore()
</script>

<template>
  <Teleport to="body">
    <div
      class="toast-container"
      aria-live="polite"
    >
      <button
        v-for="toast in toastStore.items"
        :key="toast.id"
        type="button"
        class="app-toast"
        :class="
          `app-toast--${toast.type}`
        "
        @click="
          toastStore.remove(toast.id)
        "
      >
        {{ toast.message }}
      </button>
    </div>
  </Teleport>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/AppToast.scss"
></style>
