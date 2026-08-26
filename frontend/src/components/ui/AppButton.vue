<script setup lang="ts">
withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset'
    form?: string
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
    size?: 'sm' | 'md' | 'lg'
    disabled?: boolean
    loading?: boolean
    block?: boolean
  }>(),
  {
    type: 'button',
    variant: 'primary',
    size: 'md',
    disabled: false,
    loading: false,
    block: false,
  },
)

defineEmits<{
  click: [event: MouseEvent]
}>()
</script>

<template>
  <button
    :type="type"
    :form="form"
    class="app-button"
    :class="[
      `app-button--${variant}`,
      `app-button--${size}`,
      {
        'app-button--block': block,
      },
    ]"
    :disabled="disabled || loading"
    @click="$emit('click', $event)"
  >
    <span
      v-if="loading"
      class="app-button__loader"
      aria-hidden="true"
    />

    <span class="app-button__content">
      <slot />
    </span>
  </button>
</template>

<style
  lang="scss"
  src="@/assets/styles/components/ui/AppButton.scss"
></style>
