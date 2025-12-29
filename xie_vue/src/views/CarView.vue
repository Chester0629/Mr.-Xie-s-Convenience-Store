<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import UserTopUpModal from '../components/profile/UserTopUpModal.vue'
import InlineAddressModal from '../components/profile/InlineAddressModal.vue'
import { formatPrice } from '../utils/currency'
import { resolveImageUrl } from '../utils/image'

const router = useRouter()
const toast = useToast()
const cartStore = useCartStore()
const authStore = useAuthStore()

// Use store state
const cartItems = computed(() => cartStore.items)
const cartTotal = computed(() => cartStore.totalAmount)

const availableCoupons = ref([])
const selectedCouponId = ref('')
const discountAmount = ref(0)
const appliedCoupon = ref(null)
const shippingFeeConfig = ref(60)
const freeShippingThreshold = ref(1000)

const userBalance = ref(0)
const userLevel = ref('normal')
const userAddress = ref('')
const userName = ref('')
const userPhone = ref('')
const showTopUpModal = ref(false)
const showAddressModal = ref(false)
const isProcessing = ref(false)

onMounted(() => {
  fetchSettings()
  cartStore.fetchCart()
  fetchCoupons()
  fetchUserProfile()
  fetchUserWallet()
})

async function fetchUserProfile () {
  try {
    const res = await api.get('/user')
    // Use structured address from addresses relation
    const defaultAddress = res.data.addresses?.find(a => a.is_default)
    if (defaultAddress) {
      userAddress.value = `${defaultAddress.zip_code} ${defaultAddress.city}${defaultAddress.district}${defaultAddress.detail_address}`
      userName.value = defaultAddress.recipient_name || res.data.name || ''
      userPhone.value = defaultAddress.phone || res.data.phone || ''
    } else {
      userAddress.value = ''
      userName.value = res.data.name || ''
      userPhone.value = res.data.phone || ''
    }
    userLevel.value = res.data.member_level || 'normal'
  } catch (error) {
    console.error('Fetch user profile error:', error)
  }
}

async function fetchUserWallet () {
  try {
    const res = await api.get('/user/wallet')
    userBalance.value = res.data.balance || 0
  } catch (error) {
    console.error('Fetch wallet error:', error)
  }
}

async function fetchSettings () {
  try {
    const res = await api.get('/settings')
    shippingFeeConfig.value = res.data.shipping_fee
    freeShippingThreshold.value = res.data.free_shipping_threshold
  } catch (error) {
    console.error('Fetch settings error:', error)
  }
}

async function fetchCoupons () {
  try {
    const response = await api.get('/coupons')
    availableCoupons.value = response.data
  } catch (error) {
    console.error('Fetch coupons error:', error)
  }
}

const memberDiscountAmount = computed(() => {
  return 0
})

const currentShippingFee = computed(() => {
  const netSubtotal = cartTotal.value - memberDiscountAmount.value - discountAmount.value
  return netSubtotal >= freeShippingThreshold.value ? 0 : shippingFeeConfig.value
})

const diffForFreeShipping = computed(() => {
  const netSubtotal = cartTotal.value - memberDiscountAmount.value - discountAmount.value
  return Math.max(0, freeShippingThreshold.value - netSubtotal)
})

const finalTotal = computed(() => {
  let total = cartTotal.value - memberDiscountAmount.value
  total -= discountAmount.value
  total += currentShippingFee.value
  return Math.max(0, Math.round(total))
})

async function applyCoupon () {
  if (!selectedCouponId.value) {
    discountAmount.value = 0
    appliedCoupon.value = null
    return
  }

  const coupon = availableCoupons.value.find(c => c.id === selectedCouponId.value)
  if (!coupon) return

  try {
    const response = await api.post('/coupons/check', {
      code: coupon.code,
      total_amount: cartTotal.value
    })
    appliedCoupon.value = response.data
    discountAmount.value = Math.round(response.data.discount_amount)
    toast.success(`優惠卷已套用：${response.data.message}`)
  } catch (error) {
    console.error('Coupon error:', error)
    toast.error(error.response?.data?.message || '優惠卷無效')
    discountAmount.value = 0
    appliedCoupon.value = null
    selectedCouponId.value = ''
  }
}

async function removeItem (id) {
  if (!confirm('確定要刪除此商品嗎？')) return
  try {
    await cartStore.removeItem(id)
    if (appliedCoupon.value) {
      applyCoupon()
    }
  } catch (error) {
    console.error('Remove item error (CarView):', error)
    const status = error.response?.status
    const backendMessage = error.response?.data?.message || error.response?.data?.error
    toast.error(`刪除失敗 (狀態: ${status ?? '未知'})${backendMessage ? '：' + backendMessage : ''}`)
  }
}

async function updateQuantity (item, change) {
  const newQty = item.quantity + change
  if (newQty < 1) return
  try {
    await cartStore.updateItem(item.id, newQty)
    if (appliedCoupon.value) {
      applyCoupon()
    }
  } catch (error) {
    console.error('Update quantity error (CarView):', error)
    const status = error.response?.status
    const backendMessage = error.response?.data?.message || error.response?.data?.error
    toast.error(`更新數量失敗 (狀態: ${status ?? '未知'})${backendMessage ? '：' + backendMessage : ''}`)
  }
}

async function checkout () {
  await cartStore.fetchCart()
  if (cartItems.value.length === 0) {
    toast.error('購物車是空的，請先加入商品')
    return
  }

  if (!userAddress.value) {
    showAddressModal.value = true
    return
  }

  if (!confirm('確定要送出訂單嗎？\n（訂單建立後可至會員中心付款）')) return

  isProcessing.value = true
  try {
    await api.post('/orders', {
      coupon_code: appliedCoupon.value ? appliedCoupon.value.code : null,
      shipping_address: userAddress.value || undefined,
      shipping_name: userName.value || undefined,
      shipping_phone: userPhone.value || undefined,
      skip_payment: true
    })
    toast.success('訂單已送出！請至會員中心付款')
    
    // Update cart and wallet balance (both local and global state)
    await cartStore.fetchCart()
    await fetchUserWallet() // Refresh local wallet balance
    await authStore.refreshBalance() // Refresh global auth store balance
    
    discountAmount.value = 0
    appliedCoupon.value = null
    selectedCouponId.value = ''
    
    router.push('/profile')
  } catch (error) {
    console.error('Checkout error:', error)
    const status = error.response?.status
    const backendMessage = error.response?.data?.message || error.response?.data?.error
    toast.error(`送出訂單失敗${status ? ` (狀態: ${status})` : ''}${backendMessage ? '：' + backendMessage : ''}`)
  } finally {
    isProcessing.value = false
  }
}

function handleTopUpSuccess (data) {
  userBalance.value = data.balance
  toast.success('儲值成功，您可以繼續結帳了')
}

function handleAddressSuccess (newAddress) {
  userAddress.value = newAddress
  toast.success('地址已設定，請再次點擊結帳')
}
</script>

<template>
  <div class="bg-wood-50 dark:bg-slate-900 font-sans text-slate-700 dark:text-stone-100 min-h-screen transition-colors duration-300">
    <main class="container mx-auto px-4 py-8">

        <!-- Free Shipping Banner -->
        <div class="bg-xieOrange/10 dark:bg-xieOrange/20 border border-xieOrange/30 text-slate-700 dark:text-stone-100 px-4 py-3 rounded-lg mb-6 flex items-center justify-between" v-if="diffForFreeShipping > 0">
            <div class="flex items-center gap-2">
                <i class="fas fa-truck-fast text-xieOrange"></i>
                <span>還差 <span class="font-bold text-xieOrange">{{ formatPrice(diffForFreeShipping) }}</span> 可享免運優惠！</span>
            </div>
            <router-link to="/items" class="text-sm text-xieOrange hover:underline font-medium">去湊單 &rarr;</router-link>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-2" v-else>
             <i class="fas fa-check-circle"></i>
             <span>恭喜！您已符合免運資格！</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <div class="lg:col-span-8 space-y-4">

                <!-- Table Header -->
                <div class="bg-white dark:bg-slate-800 px-6 py-3 rounded-t-lg border border-stone-100 dark:border-slate-700 hidden md:grid grid-cols-12 text-sm text-stone-500 dark:text-stone-400 font-semibold transition-colors duration-300">
                    <div class="col-span-6">商品資料</div>
                    <div class="col-span-2 text-center">單價</div>
                    <div class="col-span-2 text-center">數量</div>
                    <div class="col-span-1 text-center">小計</div>
                    <div class="col-span-1 text-right">操作</div>
                </div>

                <!-- Empty State -->
                <div v-if="cartItems.length === 0" class="bg-white dark:bg-slate-800 p-12 rounded-lg border border-stone-100 dark:border-slate-700 transition-colors duration-300">
                    <div class="flex flex-col items-center justify-center text-center">
                      <div class="w-20 h-20 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mb-6">
                        <i class="fas fa-shopping-bag text-3xl text-amber-400 dark:text-amber-500"></i>
                      </div>
                      <h3 class="text-lg font-semibold text-slate-700 dark:text-stone-100 mb-2">購物車是空的</h3>
                      <p class="text-sm text-stone-500 dark:text-stone-400 mb-6">快去選購喜歡的商品吧！</p>
                      <router-link to="/items" class="bg-xieOrange text-white px-6 py-2 rounded-md hover:bg-[#cf8354] transition font-medium">
                        開始購物
                      </router-link>
                    </div>
                </div>

                <!-- Cart Items -->
                <div v-else v-for="item in cartItems" :key="item.id" class="bg-white dark:bg-slate-800 p-4 md:px-6 md:py-4 rounded-lg border border-stone-100 dark:border-slate-700 grid grid-cols-1 md:grid-cols-12 gap-4 items-center relative group hover:border-stone-200 dark:hover:border-slate-600 transition-all duration-300">
                    <div class="col-span-6 flex gap-4 items-center">
                        <input type="checkbox" checked class="w-4 h-4 text-xieOrange focus:ring-xieOrange border-stone-300 dark:border-slate-600 rounded bg-stone-50 dark:bg-slate-700">
                        <div class="w-20 h-20 bg-stone-100 dark:bg-slate-700 rounded-lg border border-stone-200 dark:border-slate-600 flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="item.product?.image" :src="resolveImageUrl(item.product.image)" :alt="item.product?.name" class="w-full h-full object-cover">
                            <i v-else class="fas fa-box-open text-2xl text-stone-300 dark:text-slate-500"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-700 dark:text-stone-100 line-clamp-2">{{ item.product?.name || '商品載入中...' }}</h3>
                            <div v-if="item.variant" class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                              規格：{{ item.variant.options_text || '預設' }}
                            </div>
                            <div v-else class="text-xs text-stone-500 dark:text-stone-400 mt-1">規格：預設</div>
                            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1"><i class="fas fa-check-circle"></i> 24h 到貨</div>
                        </div>
                    </div>

                    <div class="col-span-2 text-center text-sm text-stone-500 dark:text-stone-400">
                        <span class="md:hidden mr-2">單價:</span>{{ formatPrice(item.effective_price || item.variant?.price || item.product?.price || 0) }}
                    </div>

                    <div class="col-span-2 flex justify-center">
                        <div class="flex items-center border border-stone-200 dark:border-slate-600 rounded-md overflow-hidden h-9">
                            <button class="px-3 bg-stone-50 dark:bg-slate-700 hover:bg-xieOrange/10 text-stone-600 dark:text-stone-300 transition" @click="updateQuantity(item, -1)" :disabled="item.quantity <= 1">-</button>
                            <input type="text" :value="item.quantity" class="w-12 text-center text-sm focus:outline-none border-x border-stone-200 dark:border-slate-600 bg-transparent text-slate-700 dark:text-stone-100" readonly>
                            <button class="px-3 bg-stone-50 dark:bg-slate-700 hover:bg-xieOrange/10 text-stone-600 dark:text-stone-300 transition" @click="updateQuantity(item, 1)">+</button>
                        </div>
                    </div>

                    <div class="col-span-1 text-center font-bold text-xieOrange">
                        {{ formatPrice((item.effective_price || item.variant?.price || item.product?.price || 0) * item.quantity) }}
                    </div>

                    <div class="col-span-1 text-right">
                        <button class="text-stone-400 dark:text-stone-500 hover:text-rose-500 transition p-2" @click="removeItem(item.id)">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-4">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-lg border border-stone-100 dark:border-slate-700 sticky top-24 transition-colors duration-300">
                    <h3 class="font-semibold text-lg mb-4 text-slate-700 dark:text-stone-100">訂單摘要</h3>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-stone-100 mb-2">使用優惠券</label>
                        <select v-model="selectedCouponId" @change="applyCoupon" class="w-full border border-stone-200 dark:border-slate-600 rounded-md px-3 py-2.5 text-sm text-stone-600 dark:text-stone-300 focus:outline-none focus:border-xieOrange bg-stone-50 dark:bg-slate-700">
                            <option value="">選擇可用的優惠券</option>
                            <option v-for="coupon in availableCoupons" :key="coupon.id" :value="coupon.id">
                                {{ coupon.code }} - {{ coupon.type === 'fixed' ? '$' + coupon.discount_amount : coupon.discount_amount + '%' }} OFF
                            </option>
                        </select>
                    </div>

                    <div class="space-y-3 text-sm text-stone-600 dark:text-stone-300 border-b border-stone-100 dark:border-slate-700 pb-4 mb-4">
                        <div class="flex justify-between">
                            <span>商品總計 ({{ cartItems.length }}件)</span>
                            <span>{{ formatPrice(cartTotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>運費 (宅配)</span>
                            <span v-if="currentShippingFee === 0" class="text-emerald-600 dark:text-emerald-400 font-semibold">免運費</span>
                            <span v-else>{{ formatPrice(currentShippingFee) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-600 dark:text-emerald-400" v-if="discountAmount > 0">
                            <span>優惠折扣</span>
                            <span>-{{ formatPrice(discountAmount) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end mb-6">
                        <span class="font-semibold text-slate-700 dark:text-stone-100">應付總金額</span>
                        <div class="text-right">
                            <span class="text-3xl font-bold text-xieOrange">{{ formatPrice(finalTotal) }}</span>
                        </div>
                    </div>

                    <!-- Order Info Note -->
                    <div class="mb-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/50 rounded-lg p-3 text-sky-700 dark:text-sky-400 text-sm flex items-start gap-2">
                         <i class="fas fa-info-circle mt-0.5"></i>
                         <div>
                             <p class="font-semibold">付款說明</p>
                             <p class="text-xs opacity-80">下單後可至會員中心付款，錢包餘額：{{ formatPrice(userBalance) }}</p>
                         </div>
                    </div>

                    <button class="w-full bg-xieOrange text-white font-semibold py-3 rounded-md text-lg hover:bg-[#cf8354] transition shadow-md shadow-xieOrange/20 mb-3 disabled:opacity-50 disabled:cursor-not-allowed" @click="checkout" :disabled="isProcessing">
                        <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
                        {{ isProcessing ? '處理中...' : '確認下單' }}
                    </button>

                    <router-link to="/items" class="block text-center text-stone-500 dark:text-stone-400 text-sm hover:text-xieOrange transition">繼續購物</router-link>
                </div>
            </div>

        </div>
    </main>
    <UserTopUpModal :visible="showTopUpModal" @close="showTopUpModal = false" @success="handleTopUpSuccess" />
    <InlineAddressModal :visible="showAddressModal" @close="showAddressModal = false" @success="handleAddressSuccess" />
  </div>
</template>

<style scoped>
/* No custom styles needed, using Tailwind classes */
</style>
