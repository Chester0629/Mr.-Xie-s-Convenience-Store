<template>
  <div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-stone-100">會員列表 ({{ totalItems }})</h2>
      <div class="flex gap-3">
        <button @click="exportToExcel" class="bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-stone-300 px-4 py-2 rounded hover:bg-gray-50 dark:hover:bg-slate-600 transition flex items-center gap-2">
          <i class="fas fa-file-export"></i> 匯出 Excel
        </button>
        <button class="bg-xieOrange text-white px-4 py-2 rounded font-bold hover:bg-orange-600 transition flex items-center gap-2" @click="$router.push('/admin/users/new')">
          <i class="fas fa-plus"></i> 新增會員
        </button>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-4 mb-6 transition-colors duration-300">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
          <label class="block text-xs font-bold text-gray-500 dark:text-stone-400 mb-1">搜尋</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-stone-500">
              <i class="fas fa-search"></i>
            </span>
            <input v-model="filters.search" type="text" placeholder="姓名 / Email / 手機" class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded text-sm focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100 placeholder:text-gray-400 dark:placeholder:text-stone-500">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 dark:text-stone-400 mb-1">會員等級</label>
          <select v-model="filters.level" class="w-full py-2 px-3 border border-gray-300 dark:border-slate-600 rounded text-sm focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100">
            <option value="">全部等級</option>
            <option value="normal">一般會員</option>
            <option value="vip">VIP 會員</option>
            <option value="platinum">白金會員</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 dark:text-stone-400 mb-1">帳號狀態</label>
          <select v-model="filters.status" class="w-full py-2 px-3 border border-gray-300 dark:border-slate-600 rounded text-sm focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100">
            <option value="">全部狀態</option>
            <option value="active">正常使用</option>
            <option value="banned">已停用 (黑名單)</option>
          </select>
        </div>

        <div class="flex items-end">
          <button @click="resetFilters" class="w-full bg-xieBlue dark:bg-sky-700 text-white py-2 rounded text-sm font-bold hover:bg-gray-700 dark:hover:bg-sky-600 transition">
            重置篩選
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm overflow-hidden transition-colors duration-300">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-stone-400 text-xs uppercase border-b border-gray-100 dark:border-slate-700">
            <th class="px-6 py-4 font-bold">會員資料</th>
            <th class="px-6 py-4 font-bold">聯絡電話</th>
            <th class="px-6 py-4 font-bold text-center">錢包餘額</th>
            <th class="px-6 py-4 font-bold text-center">等級</th>
            <th class="px-6 py-4 font-bold text-center">角色</th>
            <th class="px-6 py-4 font-bold">消費數據 (LTV)</th>
            <th class="px-6 py-4 font-bold text-center">狀態</th>
            <th class="px-6 py-4 font-bold text-right">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-slate-700 text-sm">
          <tr v-for="user in users" :key="user.id" class="hover:bg-orange-50 dark:hover:bg-slate-700/50 transition group">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-slate-600 overflow-hidden flex items-center justify-center text-gray-500 dark:text-stone-300 font-bold text-lg">
                  {{ user.name.charAt(0) }}
                </div>
                <div>
                  <div class="font-bold text-gray-800 dark:text-stone-100">{{ user.name }}</div>
                  <div class="text-xs text-gray-400 dark:text-stone-500">{{ user.email }}</div>
                  <div class="text-xs text-gray-400 dark:text-stone-500">註冊日: {{ formatDate(user.created_at) }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 text-gray-600 dark:text-stone-300">{{ user.phone || '未填寫' }}</td>
            <td class="px-6 py-4 text-center">
              <span class="font-bold text-gray-700 dark:text-stone-100">${{ formatPrice(user.balance || 0) }}</span>
            </td>
            <td class="px-6 py-4 text-center">
              <span :class="getLevelBadgeClass(user.member_level?.slug || 'normal')">
                {{ user.member_level?.name || '一般會員' }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <select 
                :value="user.role"
                @change="updateRole(user, $event.target.value)"
                class="text-xs p-1.5 rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 cursor-pointer focus:ring-2 focus:ring-xieOrange focus:border-transparent"
                :class="getRoleBadgeClass(user.role)"
              >
                <option value="customer">顧客</option>
                <option value="staff">店員</option>
                <option value="admin">管理員</option>
              </select>
            </td>
            <td class="px-6 py-4">
              <div>
                <span class="text-xs text-gray-400 dark:text-stone-500">總消費:</span>
                <span class="font-bold text-xieOrange">${{ formatPrice(user.orders_sum_total_amount || 0) }}</span>
              </div>
              <div class="text-xs text-gray-400 dark:text-stone-500">訂單數: {{ user.orders_count || 0 }} 筆</div>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-block w-2 h-2 rounded-full mr-1" :class="user.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></span>
              <span class="font-bold text-xs" :class="user.status === 'active' ? 'text-green-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                {{ user.status === 'active' ? '正常' : '已停用' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button class="text-gray-400 dark:text-stone-500 hover:text-xieOrange mx-1" title="編輯" @click="$router.push(`/admin/users/${user.id}/edit`)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="text-gray-400 dark:text-stone-500 hover:text-xieBlue dark:hover:text-sky-400 mx-1" title="查看訂單">
                <i class="fas fa-file-invoice"></i>
              </button>
            </td>
          </tr>
          <tr v-if="users.length === 0">
            <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-stone-400">
              沒有找到符合條件的會員
            </td>
          </tr>
        </tbody>
      </table>

      <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between bg-white dark:bg-slate-800" v-if="users.length > 0">
        <span class="text-xs text-gray-500 dark:text-stone-400">顯示第 {{ (currentPage - 1) * itemsPerPage + 1 }} 至 {{ Math.min(currentPage * itemsPerPage, totalItems) }} 筆，共 {{ totalItems }} 筆會員資料</span>
        <div class="flex gap-1">
          <button
            @click="fetchUsers(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-1 border border-gray-300 dark:border-slate-600 rounded text-gray-500 dark:text-stone-400 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed bg-white dark:bg-slate-800"
          >上一頁</button>

          <span class="px-3 py-1 bg-xieOrange text-white rounded text-sm font-bold">{{ currentPage }} / {{ lastPage }}</span>

          <button
            @click="fetchUsers(currentPage + 1)"
            :disabled="currentPage === lastPage"
            class="px-3 py-1 border border-gray-300 dark:border-slate-600 rounded text-gray-500 dark:text-stone-400 text-sm hover:bg-gray-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed bg-white dark:bg-slate-800"
          >下一頁</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'AdminUsers',
  data () {
    return {
      users: [],
      filters: {
        search: '',
        level: '',
        status: ''
      },
      currentPage: 1,
      lastPage: 1,
      totalItems: 0,
      itemsPerPage: 15
    }
  },
  created () {
    this.fetchUsers()
  },
  methods: {
    async fetchUsers (page = 1) {
      this.currentPage = page
      try {
        const params = {
          page: page,
          search: this.filters.search,
          status: this.filters.status
        }
        const response = await api.get('/admin/users', { params })
        this.users = response.data.data || []
        this.currentPage = response.data.current_page
        this.lastPage = response.data.last_page
        this.totalItems = response.data.total
      } catch (error) {
        console.error('Error fetching users:', error)
        this.$toast.error('無法載入會員列表')
      }
    },
    formatDate (dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('zh-TW')
    },
    formatPrice (price) {
      return price.toLocaleString()
    },
    getUserLevel (totalAmount) {
      const amount = Number(totalAmount) || 0
      if (amount >= 100000) return '白金會員'
      if (amount >= 10000) return 'VIP'
      return '一般'
    },
    getLevelBadgeClass (levelSlug) {
      if (levelSlug === 'platinum') return 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 px-2 py-1 rounded text-xs font-bold border border-purple-200 dark:border-purple-800'
      if (levelSlug === 'vip') return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 px-2 py-1 rounded text-xs font-bold border border-yellow-200 dark:border-yellow-800'
      return 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-stone-400 px-2 py-1 rounded text-xs font-bold border border-gray-200 dark:border-slate-600'
    },
    getRoleBadgeClass (role) {
      if (role === 'admin') return 'text-orange-600 dark:text-orange-400 font-bold'
      if (role === 'staff') return 'text-emerald-600 dark:text-emerald-400 font-bold'
      return 'text-gray-600 dark:text-stone-400'
    },
    async updateRole (user, newRole) {
      if (newRole === user.role) return

      const roleLabels = { admin: '管理員', staff: '店員', customer: '顧客' }
      if (!confirm(`確定要將 ${user.name} 的角色從「${roleLabels[user.role]}」變更為「${roleLabels[newRole]}」嗎？`)) {
        // Reset select to original value
        this.$forceUpdate()
        return
      }

      try {
        await api.put(`/admin/users/${user.id}/role`, { role: newRole })
        user.role = newRole
        this.$toast.success(`${user.name} 已設定為${roleLabels[newRole]}`)
      } catch (e) {
        console.error('Update role error:', e)
        this.$toast.error(e.response?.data?.message || '更新角色失敗')
        this.$forceUpdate()
      }
    },
    resetFilters () {
      this.filters = {
        search: '',
        level: '',
        status: ''
      }
      this.currentPage = 1
    },
    exportToExcel () {
      if (!confirm('注意：目前僅會匯出當前頁面的會員資料。是否繼續？')) return

      const users = this.users
      let csvContent = 'data:text/csv;charset=utf-8,'
      csvContent += 'Name,Email,Phone,Level,Total Consumption,Order Count,Status,Join Date\n'

      users.forEach(user => {
        const row = [
          user.name,
          user.email,
          user.phone || '',
          this.getUserLevel(user.orders_sum_total_amount),
          user.orders_sum_total_amount || 0,
          user.orders_count || 0,
          user.status === 'active' ? 'Active' : 'Banned',
          this.formatDate(user.created_at)
        ].map(e => `"${e}"`).join(',')
        csvContent += row + '\n'
      })

      const encodedUri = encodeURI(csvContent)
      const link = document.createElement('a')
      link.setAttribute('href', encodedUri)
      link.setAttribute('download', 'members_export.csv')
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }
  },
  watch: {
    filters: {
      handler () {
        this.fetchUsers(1)
      },
      deep: true
    }
  }
}
</script>
