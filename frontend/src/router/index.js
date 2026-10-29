import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', name: 'landing', component: () => import('../views/Landing.vue'), meta: { guestOnly: true } },
  { path: '/faq', name: 'faq', component: () => import('../views/FAQ.vue') },
  { path: '/impressum', name: 'impressum', component: () => import('../views/Impressum.vue') },
  { path: '/datenschutz', name: 'datenschutz', component: () => import('../views/Datenschutz.vue') },
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: () => import('../views/Register.vue'), meta: { guestOnly: true } },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/collection/:storageType(vault|trade_binder)',
    name: 'collection',
    component: () => import('../views/Collection.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/search',
    name: 'search',
    component: () => import('../views/CardSearch.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/scan',
    name: 'scan',
    component: () => import('../views/Scan.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/wishlist',
    name: 'wishlist',
    component: () => import('../views/Wishlist.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('../views/Settings.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/export',
    name: 'export',
    component: () => import('../views/Export.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/export/print',
    name: 'export-print',
    component: () => import('../views/PrintExport.vue'),
    meta: { requiresAuth: true, hideNav: true },
  },
  {
    path: '/user/:username/binder',
    name: 'public-binder',
    component: () => import('../views/PublicBinder.vue'),
    props: true,
  },
  {
    path: '/trades',
    name: 'trades',
    component: () => import('../views/Trades.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/trades/:id',
    name: 'trade-detail',
    component: () => import('../views/TradeDetail.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('../views/AdminDashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('../views/AdminUsers.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/content',
    name: 'admin-content',
    component: () => import('../views/AdminContent.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.ready) {
    await auth.init()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin && !auth.user?.is_admin) {
    return { name: 'dashboard' }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
