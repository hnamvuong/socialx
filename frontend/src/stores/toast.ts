import { ref } from 'vue'
import { defineStore } from 'pinia'

export type ToastType =
  | 'success'
  | 'error'
  | 'info'

export interface ToastItem {
  id: number
  message: string
  type: ToastType
}

export const useToastStore =
  defineStore(
    'toast',
    () => {
      const items =
        ref<ToastItem[]>([])

      let nextId = 1

      function remove(
        id: number,
      ): void {
        items.value =
          items.value.filter(
            (item) =>
              item.id !== id,
          )
      }

      function show(
        message: string,
        type: ToastType = 'info',
        duration = 3000,
      ): void {
        const id = nextId++

        items.value.push({
          id,
          message,
          type,
        })

        window.setTimeout(
          () => {
            remove(id)
          },
          duration,
        )
      }

      function success(
        message: string,
      ): void {
        show(
          message,
          'success',
        )
      }

      function error(
        message: string,
      ): void {
        show(
          message,
          'error',
        )
      }

      function info(
        message: string,
      ): void {
        show(
          message,
          'info',
        )
      }

      return {
        items,
        show,
        success,
        error,
        info,
        remove,
      }
    },
  )
