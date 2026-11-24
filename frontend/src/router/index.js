import { createRouter, createWebHistory } from 'vue-router'
import CashierView from '@/views/CashierView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'cashier',
      component: CashierView,
    },
  ],
})

export default router
