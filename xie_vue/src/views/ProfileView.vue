<template>
  <div class="min-h-screen bg-[#d9cdb4] dark:bg-slate-900 py-8 transition-colors duration-300">
    <!-- 未登入：重導向到登入頁 -->
    <div v-if="!isLoggedIn" class="flex justify-center items-center py-20">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin text-3xl text-xieOrange mb-4"></i>
        <p class="text-stone-500">正在跳轉...</p>
      </div>
    </div>

    <!-- 已登入：會員中心 -->
    <div v-else class="container mx-auto px-4 pb-12 grid grid-cols-1 lg:grid-cols-4 gap-6">

      <!-- Email 未驗證提醒 -->
      <div v-if="user && !user.email_verified_at" class="lg:col-span-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-400 px-5 py-4 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-800/30 rounded-full flex items-center justify-center">
            <i class="fas fa-envelope-circle-check text-amber-600 dark:text-amber-400"></i>
          </div>
          <div>
            <p class="font-medium">信箱尚未驗證</p>
            <p class="text-sm opacity-80">完成驗證即可正常使用所有功能</p>
          </div>
        </div>
        <router-link to="/verify-email" class="text-sm font-medium bg-amber-600 text-white px-4 py-2 rounded-full hover:bg-amber-700 transition">
          前往驗證
        </router-link>
      </div>

      <UserSidebar
        :user="user"
        :current-view="currentView"
        :avatar-url="avatarUrl"
        @update:currentView="currentView = $event"
        @avatar-change="onAvatarChange"
        @logout="handleLogout"
      />

      <main class="col-span-1 lg:col-span-3 space-y-6">

        <div v-if="currentView === 'dashboard'">
          <DashboardStats
            ref="dashboardStats"
            :user="user"
            :coupons="coupons"
            :orders="orders"
            @filter-selected="handleDashboardFilter"
          />
        </div>

        <OrderHistory
          v-if="currentView === 'dashboard' || currentView === 'orders'"
          :orders="orders"
          :active-tab="activeOrderTab"
          @order-updated="handleOrderUpdated"
          @filter-change="handleOrderFilterChange"
        />

        <CouponWallet
          v-if="currentView === 'coupons'"
          :coupons="coupons"
        />

        <WishlistGrid
          v-if="currentView === 'wishlist'"
          :wishlist="wishlist"
          @remove="handleWishlistRemove"
          @add-to-cart="handleAddToCart"
        />

        <AddressManager
          v-if="currentView === 'address'"
          :user="user"
          @profile-updated="handleProfileUpdated"
        />

        <ProfileEdit
          v-if="currentView === 'editProfile'"
          :user="user"
          :initial-avatar-url="avatarUrl"
          @cancel="currentView = 'dashboard'"
          @profile-updated="handleProfileUpdated"
          @avatar-change="onAvatarChange"
        />

        <WalletView v-if="currentView === 'wallet'" />

      </main>
    </div>
  </div>
</template>

<script>
import api from '../services/api'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import UserSidebar from '../components/profile/UserSidebar.vue'
import DashboardStats from '../components/profile/DashboardStats.vue'
import OrderHistory from '../components/profile/OrderHistory.vue'
import CouponWallet from '../components/profile/CouponWallet.vue'
import WishlistGrid from '../components/profile/WishlistGrid.vue'
import ProfileEdit from '../components/profile/ProfileEdit.vue'
import WalletView from '../components/profile/WalletView.vue'
import AddressManager from '../components/profile/AddressManager.vue'
import { useToast } from 'vue-toastification'

export default {
  name: 'ProfileView',
  components: {
    UserSidebar,
    DashboardStats,
    OrderHistory,
    CouponWallet,
    WishlistGrid,
    ProfileEdit,
    WalletView,
    AddressManager
  },
  setup () {
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    const toast = useToast()
    return { authStore, cartStore, toast }
  },
  data () {
    return {
      avatarUrl: 'https://img.icons8.com/color/96/000000/user-male-circle--v2.png',
      orders: [],
      coupons: [],
      wishlist: [],
      currentView: 'dashboard',
      activeOrderTab: 'all'
    }
  },
  computed: {
    isLoggedIn () {
      return this.authStore.isLoggedIn
    },
    currentUser () {
      return this.authStore.currentUser
    },
    user () {
      return this.currentUser
    }
  },
  watch: {
    '$route.query.tab': {
      immediate: true,
      handler (val) {
        if (val === 'wishlist') {
          this.currentView = 'wishlist'
        }
      }
    },
    isLoggedIn: {
      immediate: true,
      handler (val) {
        // Only redirect to login if auth is initialized and user is not logged in
        // This prevents redirect during page refresh when auth is still loading
        if (this.authStore.initialized && !val) {
          this.$router.replace('/login')
        }
      }
    },
    currentUser: {
      immediate: true,
      handler (newVal) {
        if (newVal) {
          this.fetchOrders()
          this.fetchCoupons()
          this.fetchWishlist()
        } else {
          this.orders = []
          this.coupons = []
          this.wishlist = []
        }
      }
    }
  },
  mounted () {
    // Refresh wallet balance when component is mounted (e.g., after order)
    this.$nextTick(() => {
      if (this.$refs.dashboardStats) {
        this.$refs.dashboardStats.fetchWalletBalance()
      }
    })
  },
  methods: {
    async fetchOrders (status = 'all') {
      if (!this.user) return
      try {
        const params = {}
        if (status && status !== 'all') {
          if (status === 'refunded') {
            params.status = 'cancelled'
          } else {
            params.status = status
          }
        }
        const response = await api.get('/orders', { params })
        this.orders = response.data
      } catch (error) {
        console.error('Error fetching orders:', error)
      }
    },
    handleOrderFilterChange (status) {
      this.activeOrderTab = status
      this.fetchOrders(status)
    },
    handleDashboardFilter (status) {
      this.activeOrderTab = status
      this.fetchOrders(status)
    },
    async handleOrderUpdated () {
      // Refresh orders
      await this.fetchOrders()
      // Refresh wallet balance in DashboardStats if visible
      if (this.$refs.dashboardStats) {
        this.$refs.dashboardStats.fetchWalletBalance()
      }
    },
    async fetchCoupons () {
      try {
        const response = await api.get('/coupons')
        this.coupons = response.data
      } catch (error) {
        console.error('Fetch coupons error:', error)
      }
    },
    async fetchWishlist () {
      try {
        const response = await api.get('/favorites')
        this.wishlist = response.data.map(product => ({
          ...product,
          status: product.stock > 0 ? 'available' : 'out_of_stock',
          icon_class: product.category === 'Electronics' ? 'fas fa-mobile-alt' : 'fas fa-box'
        }))
      } catch (error) {
        console.error('Fetch wishlist error:', error)
      }
    },
    async handleLogout () {
      await this.authStore.logout()
      this.toast.info('已登出')
    },
    handleProfileUpdated (updatedUser) {
      // Update the authStore's user data to reflect changes
      this.authStore.user = updatedUser
      this.currentView = 'dashboard'
    },
    onAvatarChange (file) {
      if (file) {
        const reader = new FileReader()
        reader.onload = (e) => {
          this.avatarUrl = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },
    handleWishlistRemove (id) {
      this.wishlist = this.wishlist.filter(item => item.id !== id)
    },
    async handleAddToCart (item) {
      try {
        await api.post('/cart/items', {
          product_id: item.id,
          quantity: 1
        })
        this.toast.success(`已將 ${item.name} 加入購物車`)
        this.cartStore.fetchCart()
      } catch (error) {
        console.error('Add to cart error:', error)
        this.toast.error('加入購物車失敗')
      }
    }
  }
}
</script>
