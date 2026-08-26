<script setup lang="ts">
import {
  onBeforeUnmount,
  ref,
} from 'vue'

const open = ref(false)

const root =
  ref<HTMLElement | null>(null)

function toggle(): void {
  open.value = !open.value
}

function close(): void {
  open.value = false
}

function handleDocumentClick(
  event: MouseEvent,
): void {
  const target =
    event.target as Node

  if (
    root.value &&
    !root.value.contains(target)
  ) {
    close()
  }
}

document.addEventListener(
  'click',
  handleDocumentClick,
)

onBeforeUnmount(() => {
  document.removeEventListener(
    'click',
    handleDocumentClick,
  )
})
</script>

<template>
  <div
    ref="root"
    class="app-dropdown"
  >
    <div
      class="app-dropdown__trigger"
      @click.stop="toggle"
    >
      <slot name="trigger" />
    </div>

    <div
      v-if="open"
      class="app-dropdown__menu"
      @click="close"
    >
      <slot />
    </div>
  </div>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/AppDropdown.scss"
></style>
