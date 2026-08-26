import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from './stores/theme.ts'

async function bootstrap(): Promise<void> {
  const app = createApp(App)

  const pinia = createPinia()

  app.use(pinia)
  app.use(router)

  const themeStore = useThemeStore(pinia)

  themeStore.initialize()

  const authStore = useAuthStore(pinia)

  await authStore.initialize()

  app.mount('#app')
}

void bootstrap()
