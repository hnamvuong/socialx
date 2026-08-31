import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '@/views/HomeView.vue'
import ProfileView from '@/views/ProfileView.vue'
import PostDetailView from '@/views/PostDetailView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },

    {
      path: '/@:username',
      name: 'profile',
      component: ProfileView,
    },

    {
      path: '/post/:id',
      name: 'post-detail',
      component: PostDetailView,
    },

    {
      path: '/bookmarks',
      name: 'bookmarks',
      component: () =>
        import(
          '@/views/BookmarkView.vue'
        ),
      meta: {
        requiresAuth: true,
      },
    },
  ],
})

export default router
