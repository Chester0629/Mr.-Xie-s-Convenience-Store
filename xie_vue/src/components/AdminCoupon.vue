<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { useToast } from 'vue-toastification'
import { formatPrice } from '../utils/currency'

const toast = useToast()
const coupons = ref([])
const showModal = ref(false)
const isEditing = ref(false)
const form = ref({
  id: null,
  code: '',
  discount_amount: 0,
  type: 'fixed',
  limit_price: 0,
  starts_at: '',
  ends_at: ''
})

onMounted(() => {
  fetchCoupons()
})

async function fetchCoupons () {
  try {
    const response = await api.get('/admin/coupons')
    coupons.value = response.data
  } catch (error) {
    console.error('Fetch coupons error:', error)
    toast.error('無法載入優惠卷列表')
  }
}

function formatDate (dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function openCreateModal () {
  isEditing.value = false
  form.value = {
    id: null,
    code: '',
    discount_amount: 0,
    type: 'fixed',
    limit_price: 0,
    starts_at: '',
    ends_at: ''
  }
  showModal.value = true
}

function openEditModal (coupon) {
  isEditing.value = true
  form.value = {
    ...coupon,
    discount_amount: coupon.discount_amount,
    limit_price: coupon.limit_price || 0,
    starts_at: formatDate(coupon.starts_at),
    ends_at: formatDate(coupon.ends_at)
  }
  showModal.value = true
}

async function saveCoupon () {
  try {
    const payload = { ...form.value }
    // No conversion needed - store prices directly in TWD

    if (isEditing.value) {
      await api.put(`/admin/coupons/${form.value.id}`, payload)
      toast.success('更新成功')
    } else {
      await api.post('/admin/coupons', payload)
      toast.success('新增成功')
    }
    showModal.value = false
    fetchCoupons()
  } catch (error) {
    console.error('Save coupon error:', error)
    toast.error(error.response?.data?.message || '儲存失敗')
  }
}

async function deleteCoupon (id) {
  if (!confirm('確定要刪除此優惠卷嗎？')) return
  try {
    await api.delete(`/admin/coupons/${id}`)
    toast.success('刪除成功')
    fetchCoupons()
  } catch (error) {
    console.error('Delete coupon error:', error)
    toast.error('刪除失敗')
  }
}
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-stone-100">優惠券管理</h2>
      <button class="bg-xieOrange text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition shadow-sm flex items-center gap-2" @click="openCreateModal">
        <i class="fas fa-plus"></i> 新增優惠券
      </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm overflow-hidden transition-colors duration-300">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700 text-gray-600 dark:text-stone-400 text-sm uppercase tracking-wider">
              <th class="p-4 font-bold">代碼</th>
              <th class="p-4 font-bold">折扣</th>
              <th class="p-4 font-bold">類型</th>
              <th class="p-4 font-bold">低消限制</th>
              <th class="p-4 font-bold">開始日期</th>
              <th class="p-4 font-bold">結束日期</th>
              <th class="p-4 font-bold text-right">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
            <tr v-if="coupons.length === 0">
              <td colspan="7" class="p-8 text-center text-gray-400 dark:text-stone-500">暫無優惠券資料</td>
            </tr>
            <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
              <td class="p-4 font-bold text-gray-800 dark:text-stone-100">{{ coupon.code }}</td>
              <td class="p-4 text-xieOrange font-bold">{{ coupon.type === 'fixed' ? formatPrice(coupon.discount_amount) : coupon.discount_amount + '%' }}</td>
              <td class="p-4 text-gray-600 dark:text-stone-300"><span class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded text-xs">{{ coupon.type === 'fixed' ? '定額' : '百分比' }}</span></td>
              <td class="p-4 text-gray-600 dark:text-stone-300">{{ coupon.limit_price ? formatPrice(coupon.limit_price) : '無' }}</td>
              <td class="p-4 text-gray-600 dark:text-stone-400 text-sm">{{ coupon.starts_at ? coupon.starts_at.slice(0, 10) : '即時' }}</td>
              <td class="p-4 text-gray-600 dark:text-stone-400 text-sm">{{ coupon.ends_at ? coupon.ends_at.slice(0, 10) : '永久' }}</td>
              <td class="p-4 text-right space-x-2">
                <button class="text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition" @click="openEditModal(coupon)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition" @click="deleteCoupon(coupon.id)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6 transition-colors duration-300">
        <h2 class="text-xl font-bold text-gray-800 dark:text-stone-100 mb-4">{{ isEditing ? '編輯優惠券' : '新增優惠券' }}</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-stone-200 mb-1">代碼</label>
            <input v-model="form.code" type="text" class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100" required>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-stone-200 mb-1">折扣類型</label>
            <select v-model="form.type" class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100">
              <option value="fixed">定額折扣 ($)</option>
              <option value="percentage">百分比折扣 (%)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-stone-200 mb-1">折扣數值</label>
            <input v-model.number="form.discount_amount" type="number" min="0" class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100" required>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-stone-200 mb-1">最低消費限制 (選填)</label>
            <input v-model.number="form.limit_price" type="number" min="0" class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100">
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-stone-200 mb-1">開始日期 (選填)</label>
            <input v-model="form.starts_at" type="date" class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100">
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-stone-200 mb-1">結束日期 (選填)</label>
            <input v-model="form.ends_at" type="date" class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 focus:outline-none focus:border-xieOrange bg-white dark:bg-slate-700 text-gray-800 dark:text-stone-100">
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showModal = false" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-stone-300 rounded hover:bg-gray-300 dark:hover:bg-slate-600 transition">取消</button>
          <button @click="saveCoupon" class="px-4 py-2 bg-xieOrange text-white rounded hover:bg-orange-600 transition">儲存</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Tailwind CSS is used */
</style>
