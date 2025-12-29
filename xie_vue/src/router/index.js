import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HomeView from '../views/HomeView.vue'
import ItemsView from '../views/ItemsView.vue'
import ProfileView from '../views/ProfileView.vue'
import CarView from '../views/CarView.vue'
import ProductDetail from '../views/ProductDetail.vue'
import EmailVerifyView from '../views/EmailVerifyView.vue'
const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/items',
    name: 'items',
    component: ItemsView
  },
  {
    path: '/items/:id',
    name: 'item',
    component: ProductDetail,
    props: true
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true }
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: EmailVerifyView
  },
  {
    path: '/car',
    name: 'car',
    component: CarView
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue')
  },
  {
    path: '/admin',
    component: () => import('../views/AdminView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'staff'] },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard'
      },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('../components/AdminDashboard.vue')
      },
      {
        path: 'products',
        name: 'admin-products',
        component: () => import('../components/AdminProducts.vue')
      },
      {
        path: 'orders',
        name: 'admin-orders',
        component: () => import('../components/AdminOrders.vue')
      },
      {
        path: 'orders/:id',
        name: 'admin-order-detail',
        component: () => import('../components/AdminOrderDetail.vue'),
        props: true
      },
      {
        path: 'coupons',
        name: 'admin-coupons',
        component: () => import('../components/AdminCoupon.vue'),
        meta: { roles: ['admin'] }
      },
      {
        path: 'categories',
        name: 'admin-categories',
        component: () => import('../components/AdminCategories.vue'),
        meta: { roles: ['admin'] }
      },
      {
        path: 'analytics',
        name: 'admin-analytics',
        component: () => import('../components/AdminAnalytics.vue'),
        meta: { roles: ['admin'] }
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('../components/AdminUsers.vue'),
        meta: { roles: ['admin'] }
      }
    ]
  },
  {
    path: '/admin/products/new',
    name: 'admin-product-create',
    component: () => import('../views/AdminProductEdit.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'staff'] }
  },
  {
    path: '/admin/users/new',
    name: 'admin-user-create',
    component: () => import('../views/AdminUserEdit.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/users/:id/edit',
    name: 'admin-user-edit',
    component: () => import('../views/AdminUserEdit.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/products/:id/edit',
    name: 'admin-product-edit',
    component: () => import('../views/AdminProductEdit.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'staff'] }
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // For public routes (no auth required), proceed immediately without waiting for auth
  if (!to.meta.requiresAuth) {
    // Trigger auth initialization in background (don't block navigation)
    if (!authStore.initialized && authStore.token && !authStore.user && !authStore.loading) {
      authStore.fetchUser() // Fire and forget
    }
    return next()
  }

  // For protected routes, we need to wait for auth to be initialized
  if (!authStore.initialized) {
    // Only call fetchUser if we have a token but haven't loaded user yet
    if (authStore.token && !authStore.user && !authStore.loading) {
      try {
        await authStore.fetchUser()
      } catch (e) {
        console.error('Auth check failed', e)
      }
    } else if (!authStore.token) {
      // No token - mark as initialized
      authStore.initialized = true
    }

    // If still loading, wait for it to complete (with shorter timeout)
    if (authStore.loading) {
      await new Promise(resolve => {
        const unwatch = authStore.$subscribe((mutation, state) => {
          if (!state.loading) {
            unwatch()
            resolve()
          }
        })
        // Timeout after 3 seconds to prevent long wait
        setTimeout(() => {
          unwatch()
          resolve()
        }, 3000)
      })
    }
  }

  const isLoggedIn = authStore.isLoggedIn
  const role = authStore.currentUser?.role

  if (!isLoggedIn) {
    return next({ path: '/login', query: { redirect: to.fullPath } })
  }

  if (to.meta.roles && !to.meta.roles.includes(role)) {
    return next('/')
  }

  next()
})

export default router
