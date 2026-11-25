import { createRouter, createWebHistory } from 'vue-router'
import CashierView from '@/views/CashierView.vue'
import HistoryView from '@/views/HistoryView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'cashier',
      component: CashierView,
    },
    {
      path: '/transactions',
      name: 'transactions',
      component: HistoryView,
    },
  ],
})

export default router
