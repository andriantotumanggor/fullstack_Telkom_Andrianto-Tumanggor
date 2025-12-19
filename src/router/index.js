import { createRouter, createWebHistory } from 'vue-router'
import DataTable from '../components/DataTable.vue'
import Dashboard from '../components/Dashboard.vue'

const routes = [
  {
    path: '/',
    name: 'DataTable',
    component: DataTable
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
