<template>
  <div class="space-y-6">
    <!-- Integrated Welcome & Stats Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-stone-100 dark:border-slate-700 overflow-hidden transition-colors duration-300">
      <!-- Welcome Section -->
      <div class="p-6 bg-stone-50/50 dark:bg-slate-700/30 border-b border-dashed border-stone-200 dark:border-slate-600">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-xieOrange/10 dark:bg-xieOrange/20 rounded-full flex items-center justify-center">
            <i class="fas fa-sun text-xieOrange text-xl"></i>
          </div>
          <div class="text-slate-700 dark:text-stone-100">
            <h2 class="text-xl font-bold tracking-wide">{{ greeting }}，{{ user?.name || '會員' }}！</h2>
            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">
              累積消費金額：<span class="text-xieOrange font-bold text-lg">NT$ {{ animatedSpending }}</span>
            </p>
          </div>
        </div>
      </div>
      
      <!-- Stats Section -->
      <div class="p-6">
        <div class="flex justify-center items-center gap-0">
          <!-- Balance -->
          <div class="flex-1 text-center py-2">
            <div class="text-2xl font-bold text-xieOrange tabular-nums">NT$ {{ animatedBalance }}</div>
            <div class="text-xs text-stone-500 dark:text-stone-400 mt-1 tracking-widest">錢包餘額</div>
          </div>
          <!-- Divider -->
          <div class="w-px h-12 border-l border-dashed border-stone-200 dark:border-slate-600"></div>
          <!-- Coupons -->
          <div class="flex-1 text-center py-2">
            <div class="text-2xl font-bold text-slate-700 dark:text-stone-100 tabular-nums">{{ animatedCoupons }}</div>
            <div class="text-xs text-stone-500 dark:text-stone-400 mt-1 tracking-widest">可用折價券</div>
          </div>
          <!-- Divider -->
          <div class="w-px h-12 border-l border-dashed border-stone-200 dark:border-slate-600"></div>
          <!-- Orders -->
          <div class="flex-1 text-center py-2">
            <div class="text-2xl font-bold text-slate-700 dark:text-stone-100 tabular-nums">{{ animatedOrders }}</div>
            <div class="text-xs text-stone-500 dark:text-stone-400 mt-1 tracking-widest">本月下單</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Status Grid -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-stone-100 dark:border-slate-700 p-6 transition-colors duration-300">
      <h3 class="font-semibold text-slate-700 dark:text-stone-100 mb-4 border-b border-dashed border-stone-100 dark:border-slate-700 pb-3 tracking-wide">訂單狀態一覽</h3>
      <div class="grid grid-cols-5 gap-2 text-center">
        <!-- Pending Payment -->
        <div class="group relative py-3 cursor-pointer hover:bg-stone-50 dark:hover:bg-slate-700 rounded-xl transition" @click="$emit('filter-selected', 'pending_payment')">
          <div class="w-12 h-12 mx-auto bg-stone-100 dark:bg-slate-600 rounded-full flex items-center justify-center text-stone-400 dark:text-stone-500 group-hover:text-xieOrange group-hover:bg-xieOrange/10 mb-2 transition relative">
            <i class="fas fa-wallet text-xl"></i>
            <span v-if="countStatus('pending_payment') > 0" class="absolute -top-1 -right-1 bg-amber-500 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center border-2 border-white dark:border-slate-800">{{ countStatus('pending_payment') }}</span>
          </div>
          <div class="text-sm text-stone-600 dark:text-stone-400 group-hover:text-xieOrange transition tracking-wide">待付款</div>
        </div>

        <!-- Processing -->
        <div class="group relative py-3 cursor-pointer hover:bg-stone-50 dark:hover:bg-slate-700 rounded-xl transition" @click="$emit('filter-selected', 'processing')">
          <div class="w-12 h-12 mx-auto bg-stone-100 dark:bg-slate-600 rounded-full flex items-center justify-center text-stone-400 dark:text-stone-500 group-hover:text-xieOrange group-hover:bg-xieOrange/10 mb-2 transition relative">
            <i class="fas fa-box-open text-xl"></i>
            <span v-if="countStatus('processing') > 0" class="absolute -top-1 -right-1 bg-slate-400 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center border-2 border-white dark:border-slate-800">{{ countStatus('processing') }}</span>
          </div>
          <div class="text-sm text-stone-600 dark:text-stone-400 group-hover:text-xieOrange transition tracking-wide">待出貨</div>
        </div>

        <!-- Shipped -->
        <div class="group relative py-3 cursor-pointer hover:bg-stone-50 dark:hover:bg-slate-700 rounded-xl transition" @click="$emit('filter-selected', 'shipped')">
          <div class="w-12 h-12 mx-auto bg-stone-100 dark:bg-slate-600 rounded-full flex items-center justify-center text-stone-400 dark:text-stone-500 group-hover:text-xieOrange group-hover:bg-xieOrange/10 mb-2 transition relative">
            <i class="fas fa-truck text-xl"></i>
            <span v-if="countStatus('shipped') > 0" class="absolute -top-1 -right-1 bg-sky-500 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center border-2 border-white dark:border-slate-800">{{ countStatus('shipped') }}</span>
          </div>
          <div class="text-sm text-stone-600 dark:text-stone-400 group-hover:text-xieOrange transition tracking-wide">待收貨</div>
        </div>

        <!-- Completed -->
        <div class="group relative py-3 cursor-pointer hover:bg-stone-50 dark:hover:bg-slate-700 rounded-xl transition" @click="$emit('filter-selected', 'completed')">
          <div class="w-12 h-12 mx-auto bg-stone-100 dark:bg-slate-600 rounded-full flex items-center justify-center text-stone-400 dark:text-stone-500 group-hover:text-xieOrange group-hover:bg-xieOrange/10 mb-2 transition relative">
            <i class="fas fa-clipboard-check text-xl"></i>
            <span v-if="countStatus('completed') > 0" class="absolute -top-1 -right-1 bg-emerald-500 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center border-2 border-white dark:border-slate-800">{{ countStatus('completed') }}</span>
          </div>
          <div class="text-sm text-stone-600 dark:text-stone-400 group-hover:text-xieOrange transition tracking-wide">已完成</div>
        </div>

        <!-- Cancelled/Refunded -->
        <div class="group relative py-3 cursor-pointer hover:bg-stone-50 dark:hover:bg-slate-700 rounded-xl transition" @click="$emit('filter-selected', 'refunded')">
          <div class="w-12 h-12 mx-auto bg-stone-100 dark:bg-slate-600 rounded-full flex items-center justify-center text-stone-400 dark:text-stone-500 group-hover:text-xieOrange group-hover:bg-xieOrange/10 mb-2 transition">
            <i class="fas fa-undo-alt text-xl"></i>
          </div>
          <div class="text-sm text-stone-600 dark:text-stone-400 group-hover:text-xieOrange transition tracking-wide">退貨/取消</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'DashboardStats',
  props: {
    user: Object,
    coupons: {
      type: Array,
      default: () => []
    },
    orders: {
      type: Array,
      default: () => []
    }
  },
  setup () {
    const authStore = useAuthStore()
    return { authStore }
  },
  data () {
    return {
      animatedBalance: 0,
      animatedCoupons: 0,
      animatedOrders: 0,
      animatedSpending: 0,
      walletBalance: 0
    }
  },
  computed: {
    greeting () {
      const hour = new Date().getHours()
      if (hour < 12) return '早安'
      if (hour < 18) return '午安'
      return '晚安'
    },
    totalSpending () {
      // Calculate total spending from completed orders
      if (!this.orders || this.orders.length === 0) return 0
      return this.orders
        .filter(o => o.status === 'completed' || o.status === 'shipped' || o.status === 'processing')
        .reduce((sum, o) => sum + (o.total_amount || 0), 0)
    },
    // Watch authStore balance reactively
    authBalance () {
      return this.authStore?.user?.balance
    }
  },
  watch: {
    walletBalance: {
      immediate: true,
      handler (val) {
        this.animateValue('animatedBalance', val || 0)
      }
    },
    // Watch authStore balance changes
    authBalance: {
      handler (newVal) {
        if (newVal !== undefined && newVal !== null) {
          this.walletBalance = newVal
        }
      }
    },
    coupons: {
      immediate: true,
      handler (val) {
        this.animateValue('animatedCoupons', val?.length || 0)
      }
    },
    orders: {
      immediate: true,
      handler () {
        this.animateValue('animatedOrders', this.orders?.length || 0)
        this.animateValue('animatedSpending', this.totalSpending)
      }
    }
  },
  created () {
    this.fetchWalletBalance()
  },
  methods: {
    async fetchWalletBalance () {
      try {
        const res = await api.get('/user/wallet')
        this.walletBalance = res.data.balance || 0
        // Also update authStore
        if (this.authStore?.user) {
          this.authStore.user.balance = this.walletBalance
        }
      } catch (e) {
        // Fallback to user.balance if wallet API fails
        this.walletBalance = this.user?.balance || 0
      }
    },
    countStatus (status) {
      return this.orders.filter(o => o.status === status).length
    },
    animateValue (property, target) {
      const duration = 800
      const start = this[property] || 0
      const diff = target - start
      const startTime = performance.now()
      
      const animate = (currentTime) => {
        const elapsed = currentTime - startTime
        const progress = Math.min(elapsed / duration, 1)
        
        // Ease out cubic
        const easeOut = 1 - Math.pow(1 - progress, 3)
        this[property] = Math.round(start + diff * easeOut)
        
        if (progress < 1) {
          requestAnimationFrame(animate)
        }
      }
      
      requestAnimationFrame(animate)
    }
  }
}
</script>
