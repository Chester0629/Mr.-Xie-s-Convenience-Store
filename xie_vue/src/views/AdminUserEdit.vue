<template>
  <div class="flex h-screen overflow-hidden bg-gray-100 font-sans text-gray-700">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md flex-shrink-0 hidden md:flex flex-col z-20">
      <div class="h-16 flex items-center justify-center border-b border-gray-100 bg-xieBlue text-white font-bold text-xl tracking-wider">
        MR. XIE ADMIN
      </div>

      <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1">
          <li>
            <router-link to="/admin/dashboard" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover-text-xieOrange transition">
              <i class="fas fa-chart-pie w-6"></i><span class="font-bold">總覽儀表板</span>
            </router-link>
          </li>

          <li class="px-6 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider mt-4">商店管理</li>
          <li>
            <router-link to="/admin/products" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover-text-xieOrange transition">
              <i class="fas fa-box w-6"></i><span>商品管理</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/orders" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover-text-xieOrange transition">
              <i class="fas fa-file-invoice-dollar w-6"></i><span>訂單管理</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/coupons" class="flex items-center px-6 py-3 text-gray-600 hover:bg-gray-50 hover-text-xieOrange transition">
              <i class="fas fa-ticket-alt w-6"></i><span>優惠券管理</span>
            </router-link>
          </li>

          <li class="px-6 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider mt-4">顧客與數據</li>
          <li>
            <router-link to="/admin/users" class="flex items-center px-6 py-3 text-xieOrange bg-orange-50 border-r-4 border-xieOrange">
              <i class="fas fa-users w-6"></i><span>會員管理</span>
            </router-link>
          </li>
        </ul>
      </nav>
      <div class="p-4 border-t border-gray-100">
        <button @click="handleLogout" class="flex items-center gap-3 text-sm text-gray-500 hover:text-red-500 w-full text-left">
          <i class="fas fa-sign-out-alt"></i> 登出
        </button>
      </div>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">

        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 shrink-0">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-gray-500"><i class="fas fa-bars text-xl"></i></button>
                <div class="text-lg font-bold text-gray-800">{{ isEdit ? '編輯會員資料' : '新增會員' }}</div>
            </div>
            <div class="flex items-center gap-3">
                 <button @click="$router.push('/admin/users')" class="text-gray-500 hover:text-red-500 font-bold px-4 py-2 rounded transition">取消</button>
                 <button @click="saveUser" :disabled="loading" class="bg-xieOrange text-white px-6 py-2 rounded font-bold hover:bg-orange-600 shadow-md transition flex items-center gap-2">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i> {{ loading ? '儲存中...' : '儲存變更' }}
                 </button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">

                <!-- Left Sidebar Profile Summary -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center border-t-4 border-xieOrange">
                        <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full mb-4 border-4 border-white shadow-lg overflow-hidden relative group">
                            <!-- Placeholder image, eventually can be dynamic -->
                            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer">
                                <i class="fas fa-camera text-white"></i>
                            </div>
                        </div>
                        <h3 class="font-bold text-lg text-gray-800">{{ form.name || 'New User' }}</h3>
                        <p class="text-gray-500 text-sm mb-3">{{ form.email || 'email@example.com' }}</p>

                        <div class="flex justify-center gap-2 mb-6">
                            <span :class="memberLevelClass" class="text-white text-xs px-2 py-1 rounded font-bold">{{ memberLevelName }}</span>
                            <span :class="form.status === 'active' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="text-xs px-2 py-1 rounded font-bold">
                                {{ form.status === 'active' ? '帳號正常' : '已停用' }}
                            </span>
                        </div>

                        <!-- Component: Wallet Balance Card -->
                        <WalletBalanceCard
                          v-if="isEdit"
                          :balance="balance"
                          @open-modal="openWalletModal"
                        />

                        <!-- Stats only for Edit Mode -->
                        <div v-if="isEdit" class="grid grid-cols-2 gap-2 border-t border-b border-gray-100 py-4 mb-4">
                            <div>
                                <div class="text-xs text-gray-400">總消費金額</div>
                                <div class="font-bold text-lg text-xieOrange">NT$ {{ totalSpent }}</div>
                            </div>
                            <div class="border-l border-gray-100">
                                <div class="text-xs text-gray-400">訂單數量</div>
                                <div class="font-bold text-lg text-gray-700">{{ orderCount }}</div>
                            </div>
                        </div>

                        <div class="text-left">
                            <label class="text-xs font-bold text-gray-500 mb-1 block">管理員備註</label>
                            <textarea v-model="form.memo" class="w-full text-sm border border-gray-200 rounded p-2 focus:outline-none focus-border-xieOrange" rows="4" placeholder="例如：大戶、喜歡殺價..."></textarea>
                        </div>
                    </div>

                    <div v-if="isEdit" class="bg-white rounded-lg shadow-sm p-4">
                        <button @click="toggleBan" class="w-full text-red-500 border border-red-500 rounded py-2 text-sm font-bold hover:bg-red-50 transition">
                            <i class="fas fa-ban mr-2"></i> {{ form.status === 'banned' ? '啟用此帳號' : '停用此帳號 (黑名單)' }}
                        </button>
                    </div>
                </div>

                <!-- Main Content Tabs -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden min-h-[600px]">

                        <div class="flex border-b border-gray-200">
                            <button @click="activeTab = 'basic'" :class="activeTab === 'basic' ? 'text-xieOrange border-b-2 border-xieOrange bg-orange-50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-6 py-4 font-bold transition focus:outline-none">基本資料</button>
                            <button @click="activeTab = 'wallet'" :class="activeTab === 'wallet' ? 'text-xieOrange border-b-2 border-xieOrange bg-orange-50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-6 py-4 font-bold transition focus:outline-none">錢包紀錄</button>
                            <button @click="activeTab = 'orders'" :class="activeTab === 'orders' ? 'text-xieOrange border-b-2 border-xieOrange bg-orange-50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-6 py-4 font-bold transition focus:outline-none">消費紀錄</button>
                            <button @click="activeTab = 'address'" :class="activeTab === 'address' ? 'text-xieOrange border-b-2 border-xieOrange bg-orange-50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-6 py-4 font-bold transition focus:outline-none">收貨地址</button>
                        </div>

                        <div class="p-8">

                            <!-- Basic Info Tab -->
                            <div v-if="activeTab === 'basic'">
                                <div class="mb-8">
                                    <h3 class="font-bold text-gray-800 mb-4 border-l-4 border-xieOrange pl-3">帳號安全</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-bold text-gray-700 mb-2">電子信箱 {{ isEdit ? '(不可修改)' : '' }} <span v-if="!isEdit" class="text-red-500">*</span></label>
                                            <input v-model="form.email" type="email" :disabled="isEdit" :class="isEdit ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''" placeholder="將作為會員登入帳號" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange focus:ring-1 focus-ring-xieOrange">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">{{ isEdit ? '重設密碼' : '預設密碼' }} <span v-if="!isEdit" class="text-red-500">*</span></label>
                                            <input v-model="form.password" type="password" :placeholder="isEdit ? '若不修改請留空' : '請設定初始密碼'" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">確認{{ isEdit ? '新' : '' }}密碼 <span v-if="!isEdit" class="text-red-500">*</span></label>
                                            <input v-model="form.password_confirmation" type="password" :placeholder="isEdit ? '再次輸入新密碼' : '再次輸入密碼'" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange">
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-100 my-6">

                                <div>
                                    <h3 class="font-bold text-gray-800 mb-4 border-l-4 border-xieOrange pl-3">個人資料</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">真實姓名 <span class="text-red-500">*</span></label>
                                            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange focus:ring-1 focus-ring-xieOrange">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">手機號碼</label>
                                            <input v-model="form.phone" type="tel" placeholder="09xx-xxx-xxx" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange focus:ring-1 focus-ring-xieOrange">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">生日</label>
                                            <input v-model="form.birthday" type="date" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">性別</label>
                                            <div class="flex gap-4 mt-2">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" value="male" v-model="form.gender" class="text-xieOrange focus-ring-xieOrange">
                                                    <span class="ml-2 text-sm">男</span>
                                                </label>
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" value="female" v-model="form.gender" class="text-xieOrange focus-ring-xieOrange">
                                                    <span class="ml-2 text-sm">女</span>
                                                </label>
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" value="other" v-model="form.gender" class="text-xieOrange focus-ring-xieOrange">
                                                    <span class="ml-2 text-sm">不透露</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">會員等級</label>
                                            <div class="flex items-center gap-2">
                                                <select v-model="form.member_level" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange">
                                                    <option value="normal">一般會員</option>
                                                    <option value="vip">VIP 會員</option>
                                                    <option value="platinum">白金會員</option>
                                                </select>
                                                <label class="flex items-center gap-1 cursor-pointer whitespace-nowrap">
                                                    <input type="checkbox" v-model="form.is_level_locked" class="text-xieOrange rounded focus:ring-xieOrange">
                                                    <span class="text-sm font-bold text-gray-600">鎖定等級</span>
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-400 mt-1" v-if="form.is_level_locked">
                                                <i class="fas fa-lock text-xieOrange"></i> 此會員等級已鎖定，系統不會自動調整
                                            </p>
                                        </div>

                                        <!-- Status Select instead of newsletter which is less relevant for admin edit here -->
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">帳號狀態</label>
                                            <select v-model="form.status" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange">
                                                <option value="active">正常 (Active)</option>
                                                <option value="banned">停用 (Banned)</option>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                            </div>

                             <!-- Wallet Transactions Tab (Refactored) -->
                            <div v-if="activeTab === 'wallet'">
                              <WalletTransactionTable
                                :transactions="transactions"
                                @open-modal="openWalletModal"
                              />
                            </div>

                            <!-- Address Tab -->
                            <div v-if="activeTab === 'address'">
                                <h3 class="font-bold text-gray-800 mb-4 border-l-4 border-xieOrange pl-3">收貨地址</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">縣市</label>
                                        <select v-model="form.city" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus-border-xieOrange">
                                            <option value="" disabled>選擇縣市</option>
                                            <option v-for="c in taiwanDistricts" :key="c.city" :value="c.city">{{ c.city }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">區域</label>
                                        <select v-model="form.district" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus-border-xieOrange" :disabled="!form.city">
                                            <option value="" disabled>選擇區域</option>
                                            <option v-for="d in availableDistricts" :key="d.name" :value="d.name">{{ d.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">郵遞區號</label>
                                        <input v-model="form.zip_code" type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus-border-xieOrange" readonly placeholder="自動帶入">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">詳細地址</label>
                                    <input v-model="form.detail_address" type="text" placeholder="街道、巷弄、門牌號碼、樓層" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus-border-xieOrange">
                                </div>
                            </div>

                            <!-- Orders Tab Placeholder -->
                             <div v-if="activeTab === 'orders'">
                                <div v-if="orders.length > 0" class="overflow-x-auto">
                                   <table class="w-full text-sm text-left">
                                      <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3">訂單編號</th>
                                            <th class="px-4 py-3">日期</th>
                                            <th class="px-4 py-3">金額</th>
                                            <th class="px-4 py-3">狀態</th>
                                            <th class="px-4 py-3">操作</th>
                                        </tr>
                                      </thead>
                                      <tbody class="divide-y divide-gray-100">
                                          <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50 transition">
                                              <td class="px-4 py-3 font-bold text-gray-700">#{{ order.id }}</td>
                                              <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(order.created_at).toLocaleString() }}</td>
                                              <td class="px-4 py-3 font-bold text-gray-800">NT$ {{ order.total_amount }}</td>
                                              <td class="px-4 py-3">
                                                  <span class="px-2 py-1 rounded text-xs font-bold"
                                                    :class="{
                                                        'bg-red-100 text-red-600': order.status === 'pending_payment',
                                                        'bg-blue-100 text-blue-600': order.status === 'shipped',
                                                        'bg-green-100 text-green-600': order.status === 'completed',
                                                        'bg-gray-100 text-gray-600': order.status === 'processing',
                                                        'bg-yellow-100 text-yellow-600': order.status === 'cancelled'
                                                    }">
                                                    {{ order.status }}
                                                  </span>
                                              </td>
                                              <td class="px-4 py-3">
                                                  <router-link :to="`/admin/orders/${order.id}`" class="text-xieOrange hover:text-orange-600 font-bold text-xs border border-xieOrange px-2 py-1 rounded hover:bg-orange-50 transition">
                                                      查看
                                                  </router-link>
                                              </td>
                                          </tr>
                                      </tbody>
                                   </table>
                                </div>
                                <div v-else class="text-center py-12 text-gray-500">
                                    <i class="fas fa-box-open text-4xl mb-4 text-gray-300"></i>
                                    <p>暫無消費紀錄</p>
                                </div>
                             </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Transaction Modal (Refactored) -->
            <WalletActionModal
              :visible="showWalletModal"
              :loading="walletLoading"
              @close="closeWalletModal"
              @submit="saveWalletTransaction"
            />

        </main>
    </div>

  </div>
</template>

<script>
import api from '../services/api'
import { useAuthStore } from '@/stores/auth'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import taiwanDistricts from '../assets/taiwan_districts.json'

// Import new components
import WalletBalanceCard from '../components/admin/WalletBalanceCard.vue'
import WalletTransactionTable from '../components/admin/WalletTransactionTable.vue'
import WalletActionModal from '../components/admin/WalletActionModal.vue'

export default {
  name: 'AdminUserEdit',
  components: {
    WalletBalanceCard,
    WalletTransactionTable,
    WalletActionModal
  },
  setup () {
    const toast = useToast()
    const authStore = useAuthStore()
    return { toast, authStore }
  },
  data () {
    return {
      loading: false,
      activeTab: 'basic',
      balance: 0,
      orderCount: 0,
      totalSpent: 0,
      transactions: [],
      orders: [],
      showWalletModal: false,
      walletLoading: false,
      memberLevelData: null,
      form: {
        email: '',
        name: '',
        password: '',
        password_confirmation: '',
        phone: '',
        birthday: '',
        gender: 'other',
        status: 'active',
        memo: '',
        member_level: 'normal',
        is_level_locked: false,
        member_level: 'normal',
        is_level_locked: false,
        newsletter: true,
        // Address fields
        city: '',
        district: '',
        zip_code: '',
        detail_address: ''
      },
      taiwanDistricts
    }
  },
  computed: {
    isEdit () {
      return !!this.$route.params.id
    },
    memberLevelName () {
      if (this.memberLevelData?.name) {
        return this.memberLevelData.name
      }
      const levelMap = {
        normal: '一般會員',
        vip: 'VIP 會員',
        platinum: '白金會員'
      }
      return levelMap[this.form.member_level] || '一般會員'
    },
    availableDistricts () {
      if (!this.form.city) return []
      const cityData = this.taiwanDistricts.find(c => c.city === this.form.city)
      return cityData ? cityData.districts : []
    },
    fullAddress () {
      return `${this.form.zip_code} ${this.form.city}${this.form.district}${this.form.detail_address}`
    },
    memberLevelClass () {
      const slug = this.memberLevelData?.slug || this.form.member_level
      const classMap = {
        normal: 'bg-gray-500',
        vip: 'bg-blue-600',
        platinum: 'bg-gradient-to-r from-amber-500 to-orange-600'
      }
      return classMap[slug] || 'bg-gray-500'
    }
  },
  watch: {
    'form.city' (newVal) {
      const cityData = this.taiwanDistricts.find(c => c.city === newVal)
      if (cityData) {
        const validDistricts = cityData.districts.map(d => d.name)
        if (!validDistricts.includes(this.form.district)) {
          this.form.district = ''
          this.form.zip_code = ''
        }
      }
    },
    'form.district' (newVal) {
      if (newVal && this.form.city) {
        const cityData = this.taiwanDistricts.find(c => c.city === this.form.city)
        if (cityData) {
          const districtData = cityData.districts.find(d => d.name === newVal)
          if (districtData) {
            this.form.zip_code = districtData.zip
          }
        }
      }
    }
  },
  created () {
    if (this.isEdit) {
      this.fetchUser(this.$route.params.id)
    }
  },
  methods: {
    async fetchUser (id) {
      try {
        const res = await api.get(`/admin/users/${id}`)
        const user = res.data
        this.form = {
          email: user.email,
          name: user.name,
          phone: user.phone || '',
          birthday: user.birthday || '',
          gender: user.gender || 'other',
          status: user.status || 'active',
          memo: user.memo || '',
          newsletter: user.newsletter !== undefined ? user.newsletter : true,
          member_level: (typeof user.member_level === 'object' && user.member_level !== null) 
            ? (user.member_level.slug || 'normal') 
            : (user.member_level || 'normal'),
          is_level_locked: !!user.is_level_locked,
          password: '',
          password_confirmation: '',
          // Address fields
          city: '',
          district: '',
          zip_code: '',
          detail_address: ''
        }

        // Initialize address if available
        if (user.addresses && user.addresses.length > 0) {
          const defaultAddr = user.addresses.find(a => a.is_default) || user.addresses[0]
          this.form.city = defaultAddr.city || ''
          this.form.district = defaultAddr.district || ''
          this.form.zip_code = defaultAddr.zip_code || ''
          this.form.detail_address = defaultAddr.detail_address || ''
          
          // Legacy address fallback
          if (!this.form.detail_address && user.address) {
            this.form.detail_address = user.address
          }
        } else if (user.address) {
          // Fallback for old address format
          this.form.detail_address = user.address
        }
        // Set Wallet & Order Data
        this.balance = user.balance || 0
        this.memberLevelData = (typeof user.member_level === 'object') ? user.member_level : null
        this.transactions = user.wallet_transactions || []
        this.orders = user.orders || []
        // Helper to safely access aggregates
        this.orderCount = user.orders_count || 0
        this.totalSpent = user.orders_sum_total_amount || 0
      } catch (e) {
        console.error(e)
        this.toast.error('無法載入會員資料')
        this.$router.push('/admin/users')
      }
    },
    handleLogout () {
      this.authStore.logout()
    },
    toggleBan () {
      this.form.status = this.form.status === 'banned' ? 'active' : 'banned'
    },
    async saveUser () {
      if (!this.form.email || !this.form.name) {
        this.toast.warning('請填寫必填欄位 (Email, 姓名)')
        return
      }

      if (!this.isEdit && !this.form.password) {
        this.toast.warning('新增會員請設定密碼')
        return
      }

      if (this.form.password && this.form.password !== this.form.password_confirmation) {
        this.toast.warning('確認密碼不一致')
        return
      }

      this.loading = true
      try {
        const payload = {
          ...this.form
        }
        if (!payload.password) delete payload.password
        delete payload.password_confirmation

        if (this.isEdit) {
          await api.put(`/admin/users/${this.$route.params.id}`, payload)
        } else {
          await api.post('/admin/users', payload)
        }

        this.toast.success('儲存成功')
        if (!this.isEdit) {
          this.$router.push('/admin/users')
        }
      } catch (e) {
        console.error(e)
        const msg = e.response?.data?.message || '儲存失敗'
        this.toast.error('錯誤: ' + msg)
      } finally {
        this.loading = false
      }
    },
    // Wallet Methods
    openWalletModal () {
      this.showWalletModal = true
    },
    closeWalletModal () {
      this.showWalletModal = false
    },
    async saveWalletTransaction (data) {
      if (!data.amount || data.amount <= 0) {
        this.toast.warning('請輸入有效金額')
        return
      }
      if (!data.description) {
        this.toast.warning('請輸入備註說明')
        return
      }

      this.walletLoading = true
      try {
        const userId = this.$route.params.id
        await api.post(`/admin/users/${userId}/wallet/transaction`, data)

        this.toast.success('交易成功')
        this.closeWalletModal()

        // Refresh User Data (Balance & Transactions)
        this.fetchUser(userId)
      } catch (e) {
        console.error(e)
        const msg = e.response?.data?.message || '交易失敗'
        this.toast.error('錯誤: ' + msg)
      } finally {
        this.walletLoading = false
      }
    }
  }
}
</script>

<style scoped>
/* Scoped styles reuse from AdminProductEdit or tailwind */
.bg-xieBlue { background-color: #2d3748; }
.text-xieBlue { color: #2d3748; }
.bg-xieOrange { background-color: #ed8936; }
.text-xieOrange { color: #ed8936; }
.border-xieOrange { border-color: #ed8936; }
.focus-border-xieOrange:focus { border-color: #ed8936; }
.focus-ring-xieOrange:focus { --tw-ring-color: #ed8936; }
.bg-orange-50 { background-color: #fffaf0; }
.hover-bg-orange-50:hover { background-color: #fffaf0; }
.hover-text-xieOrange:hover { color: #ed8936; }

nav::-webkit-scrollbar {
  width: 4px;
}
nav::-webkit-scrollbar-track {
  background: transparent;
}
nav::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 20px;
}
</style>
