<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-stone-100 dark:border-slate-700 overflow-hidden transition-colors duration-300">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-stone-100 dark:border-slate-700">
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-slate-700 dark:text-stone-100 flex items-center">
            購物紀錄
            <span v-if="currentTab !== 'all'" class="ml-2 text-sm font-normal text-stone-500 dark:text-stone-400 bg-stone-100 dark:bg-slate-700 px-2.5 py-1 rounded-full">
              {{ filterTitle }}
            </span>
          </h3>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left" v-if="orders.length > 0">
          <thead class="bg-stone-50 dark:bg-slate-700 text-stone-600 dark:text-stone-300 font-medium">
            <tr>
              <th class="px-6 py-3">訂單編號</th>
              <th class="px-6 py-3">下單日期</th>
              <th class="px-6 py-3">總金額</th>
              <th class="px-6 py-3">狀態</th>
              <th class="px-6 py-3 text-right">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100 dark:divide-slate-700">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-stone-50 dark:hover:bg-slate-700/50 transition">
              <td class="px-6 py-4">
                <span class="text-stone-400 dark:text-stone-500">#</span><span class="font-mono font-bold text-slate-700 dark:text-stone-100">{{ order.id }}</span>
              </td>
              <td class="px-6 py-4 text-stone-500 dark:text-stone-400">{{ formatDate(order.created_at) }}</td>
              <td class="px-6 py-4 font-bold text-slate-700 dark:text-stone-100">{{ formatPrice(order.total_amount) }}</td>
              <td class="px-6 py-4">
                <span 
                  class="py-1 px-3 rounded-full text-xs font-medium"
                  :class="getStatusClass(order.status)"
                >
                  {{ getStatusText(order.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    v-if="order.status === 'pending_payment'" 
                    class="bg-xieOrange text-white px-3 py-1.5 rounded-full text-xs font-medium hover:bg-[#cf8354] transition" 
                    @click="openPaymentModal(order)"
                  >
                    去付款
                  </button>
                  <button 
                    v-if="order.status === 'processing'" 
                    class="bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 px-3 py-1.5 rounded-full text-xs font-medium hover:bg-rose-100 dark:hover:bg-rose-900/30 transition" 
                    @click="cancelOrder(order)"
                  >
                    取消訂單
                  </button>
                  <button 
                    class="w-8 h-8 rounded-full border border-stone-200 dark:border-slate-600 text-stone-400 hover:text-xieOrange hover:border-xieOrange flex items-center justify-center transition" 
                    @click="openOrderDetails(order.id)"
                    title="詳情"
                  >
                    <i class="fas fa-chevron-right text-xs"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="p-12 text-center text-stone-400 dark:text-stone-500">
          <i class="fas fa-receipt text-4xl mb-3 opacity-50"></i>
          <p>尚無訂單紀錄</p>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showPaymentModal && paymentOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showPaymentModal = false">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-stone-100 dark:border-slate-700 bg-stone-50/50 dark:bg-slate-700/30">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-xieOrange/10 rounded-full flex items-center justify-center text-xieOrange">
                  <i class="fas fa-credit-card text-lg"></i>
                </div>
                <div>
                  <h3 class="font-bold text-slate-700 dark:text-stone-100">付款訂單 #{{ paymentOrder.id }}</h3>
                  <p class="text-xs text-stone-500 dark:text-stone-400">請確認餘額後付款</p>
                </div>
              </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
              <!-- Order Amount -->
              <div class="flex justify-between items-center py-3 border-b border-dashed border-stone-100 dark:border-slate-700">
                <span class="text-stone-500 dark:text-stone-400">訂單金額</span>
                <span class="text-2xl font-bold text-slate-700 dark:text-stone-100">{{ formatPrice(paymentOrder.total_amount) }}</span>
              </div>

              <!-- Wallet Balance -->
              <div class="flex justify-between items-center py-3 border-b border-dashed border-stone-100 dark:border-slate-700">
                <span class="text-stone-500 dark:text-stone-400">我的錢包餘額</span>
                <div class="flex items-center gap-3">
                  <span class="text-xl font-bold" :class="isBalanceSufficient ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                    {{ formatPrice(walletBalance) }}
                  </span>
                  <button 
                    @click="showTopUpModal = true"
                    class="px-3 py-1.5 text-xs font-medium bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 rounded-full hover:bg-sky-100 dark:hover:bg-sky-900/50 transition"
                  >
                    <i class="fas fa-plus-circle mr-1"></i>儲值
                  </button>
                </div>
              </div>

              <!-- Balance Warning -->
              <div v-if="!isBalanceSufficient" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                  <p class="font-medium text-amber-800 dark:text-amber-400">餘額不足</p>
                  <p class="text-sm text-amber-700 dark:text-amber-300 opacity-80">還差 {{ formatPrice(balanceShortfall) }}，請先儲值</p>
                </div>
              </div>

              <!-- Sufficient Balance -->
              <div v-else class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div>
                  <p class="font-medium text-emerald-800 dark:text-emerald-400">餘額充足</p>
                  <p class="text-sm text-emerald-700 dark:text-emerald-300 opacity-80">付款後餘額：{{ formatPrice(walletBalance - paymentOrder.total_amount) }}</p>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-stone-50 dark:bg-slate-700/50 flex gap-3 border-t border-stone-100 dark:border-slate-600">
              <button 
                class="flex-1 py-3 border border-stone-300 dark:border-slate-500 rounded-xl text-stone-600 dark:text-stone-300 font-medium hover:bg-stone-100 dark:hover:bg-slate-600 transition" 
                @click="showPaymentModal = false"
              >
                取消
              </button>
              <button 
                class="flex-[2] py-3 bg-xieOrange text-white rounded-xl font-semibold hover:bg-[#cf8354] transition shadow-lg shadow-xieOrange/20 disabled:opacity-50 disabled:cursor-not-allowed" 
                :disabled="!isBalanceSufficient || paymentLoading"
                @click="confirmPayment"
              >
                <i v-if="paymentLoading" class="fas fa-spinner fa-spin mr-2"></i>
                {{ paymentLoading ? '付款中...' : '確認付款' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Order Details Modal -->
    <div v-if="showOrderDetails && selectedOrder" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showOrderDetails = false">
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-stone-100 dark:border-slate-700 shadow-xl w-full max-w-lg mx-4 overflow-hidden animate-fade-in">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-stone-100 dark:border-slate-700 flex justify-between items-center">
          <h3 class="font-bold text-lg text-slate-700 dark:text-stone-100">訂單詳情 #{{ selectedOrder.id }}</h3>
          <button class="w-8 h-8 rounded-full hover:bg-stone-100 dark:hover:bg-slate-700 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 flex items-center justify-center transition" @click="showOrderDetails = false">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-stone-500 dark:text-stone-400">下單日期</div>
            <div class="font-medium text-slate-700 dark:text-stone-100 text-right">{{ new Date(selectedOrder.created_at).toLocaleString() }}</div>
            <div class="text-stone-500 dark:text-stone-400">訂單狀態</div>
            <div class="font-medium text-right text-xieOrange">{{ getStatusText(selectedOrder.status) }}</div>
            <div class="text-stone-500 dark:text-stone-400">物流狀態</div>
            <div class="font-medium text-right text-sky-600 dark:text-sky-400">{{ getLogisticsStatus(selectedOrder.status) }}</div>
            <div class="text-stone-500 dark:text-stone-400">總金額</div>
            <div class="font-bold text-right text-xl text-slate-700 dark:text-stone-100">{{ formatPrice(selectedOrder.total_amount) }}</div>
          </div>

          <div class="border-t border-stone-100 dark:border-slate-700 pt-4">
            <h4 class="font-semibold text-slate-700 dark:text-stone-100 mb-3 text-sm">商品列表</h4>
            <ul class="space-y-2">
              <li v-for="item in selectedOrder.items" :key="item.id" class="flex justify-between items-center text-sm">
                <span class="text-slate-700 dark:text-stone-200 truncate flex-1 mr-4">{{ item.product ? item.product.name : '未知商品' }}</span>
                <div class="text-stone-500 dark:text-stone-400 whitespace-nowrap">
                  <span>x{{ item.quantity }}</span>
                  <span class="ml-3 font-bold text-slate-700 dark:text-stone-100">{{ formatPrice(item.price * item.quantity) }}</span>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-stone-50 dark:bg-slate-700 flex justify-end gap-3 border-t border-stone-100 dark:border-slate-600">
          <button class="px-5 py-2 border border-stone-300 dark:border-slate-500 rounded-full text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-slate-600 transition text-sm font-medium" @click="showOrderDetails = false">
            關閉
          </button>
          <button 
            v-if="selectedOrder.status === 'pending_payment'" 
            class="px-6 py-2 bg-xieOrange text-white rounded-full font-medium hover:bg-[#cf8354] transition text-sm shadow-lg shadow-xieOrange/20" 
            @click="showOrderDetails = false; openPaymentModal(selectedOrder)"
          >
            立即付款
          </button>
        </div>
      </div>
    </div>

    <!-- Top Up Modal -->
    <UserTopUpModal
      :visible="showTopUpModal"
      @close="showTopUpModal = false"
      @success="handleTopUpSuccess"
    />
  </div>
</template>

<script>
import api from '../../services/api'
import { ORDER_STATUS_LABELS, LOGISTICS_STATUS_LABELS } from '../../utils/constants'
import { formatPrice } from '../../utils/currency'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '../../stores/auth'
import UserTopUpModal from './UserTopUpModal.vue'

export default {
  name: 'OrderHistory',
  components: {
    UserTopUpModal
  },
  props: {
    orders: {
      type: Array,
      default: () => []
    },
    activeTab: {
      type: String,
      default: 'all'
    }
  },
  emits: ['order-updated', 'filter-change'],
  setup () {
    const toast = useToast()
    const authStore = useAuthStore()
    return { toast, authStore }
  },
  data () {
    return {
      showOrderDetails: false,
      selectedOrder: null,
      currentTab: this.activeTab,
      // Payment modal state
      showPaymentModal: false,
      paymentOrder: null,
      walletBalance: 0,
      showTopUpModal: false,
      paymentLoading: false
    }
  },
  computed: {
    filterTitle () {
      return ORDER_STATUS_LABELS[this.currentTab] || this.currentTab
    },
    isBalanceSufficient () {
      if (!this.paymentOrder) return false
      return this.walletBalance >= this.paymentOrder.total_amount
    },
    balanceShortfall () {
      if (!this.paymentOrder) return 0
      return Math.max(0, this.paymentOrder.total_amount - this.walletBalance)
    }
  },
  watch: {
    activeTab (newVal) {
      if (newVal !== this.currentTab) {
        this.currentTab = newVal
      }
    },
    currentTab (newVal) {
      if (newVal !== this.activeTab) {
        this.$emit('filter-change', newVal)
      }
    }
  },
  methods: {
    formatPrice,
    formatDate (dateString) {
      return new Date(dateString).toLocaleDateString()
    },
    getStatusText (status) {
      return ORDER_STATUS_LABELS[status] || status
    },
    getLogisticsStatus (status) {
      return LOGISTICS_STATUS_LABELS[status] || status
    },
    getStatusClass (status) {
      const classes = {
        pending_payment: 'bg-amber-50/80 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-[0.5px] border-amber-200/50 dark:border-amber-700/50',
        processing: 'bg-slate-100 dark:bg-slate-600 text-slate-600 dark:text-slate-300 border-[0.5px] border-slate-200/50 dark:border-slate-500/50',
        shipped: 'bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 border-[0.5px] border-sky-200/50 dark:border-sky-700/50',
        completed: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-[0.5px] border-emerald-200/50 dark:border-emerald-700/50',
        cancelled: 'bg-stone-100 dark:bg-slate-600 text-stone-500 dark:text-stone-400 border-[0.5px] border-stone-200/50 dark:border-slate-500/50'
      }
      return classes[status] || 'bg-stone-100 dark:bg-slate-600 text-stone-600 dark:text-stone-300 border-[0.5px] border-stone-200/50'
    },
    async fetchWalletBalance () {
      try {
        const res = await api.get('/user/wallet')
        this.walletBalance = res.data.balance || 0
      } catch (e) {
        console.error('Fetch wallet error:', e)
      }
    },
    async openPaymentModal (order) {
      this.paymentOrder = order
      await this.fetchWalletBalance()
      this.showPaymentModal = true
    },
    handleTopUpSuccess (data) {
      this.walletBalance = data.balance
      this.showTopUpModal = false
      this.toast.success('儲值成功！')
    },
    async confirmPayment () {
      if (!this.paymentOrder || !this.isBalanceSufficient) return

      this.paymentLoading = true
      try {
        await api.post(`/orders/${this.paymentOrder.id}/pay`)
        this.toast.success('付款成功！')
        this.showPaymentModal = false
        this.paymentOrder = null
        
        // Refresh wallet balance after successful payment
        await this.fetchWalletBalance()
        await this.authStore.refreshBalance()
        
        this.$emit('order-updated')
      } catch (error) {
        console.error('Payment error:', error)
        if (error.response?.data?.error_code === 'INSUFFICIENT_BALANCE') {
          this.toast.error('餘額不足，請先儲值')
          await this.fetchWalletBalance()
        } else {
          this.toast.error(error.response?.data?.message || '付款失敗')
        }
      } finally {
        this.paymentLoading = false
      }
    },
    async openOrderDetails (orderId) {
      try {
        const response = await api.get(`/orders/${orderId}`)
        this.selectedOrder = response.data
        this.showOrderDetails = true
      } catch (error) {
        console.error('Fetch order details error:', error)
        this.toast.error('無法取得訂單詳情')
      }
    },
    async cancelOrder (order) {
      if (!confirm(`確定要取消訂單 #${order.id} 嗎？\n(若已付款將全額退款至錢包)`)) return

      try {
        await api.post(`/orders/${order.id}/refund`)
        this.toast.success('訂單已取消並退款')
        this.$emit('order-updated')
      } catch (error) {
        console.error('Refund error:', error)
        this.toast.error(error.response?.data?.message || '取消失敗')
      }
    }
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
