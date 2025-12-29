<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">商品規格管理</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ product?.name }}</p>
      </div>
      <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <i class="fas fa-spinner fa-spin text-3xl text-xieOrange"></i>
      <p class="mt-2 text-gray-500">載入中...</p>
    </div>

    <div v-else class="space-y-8">
      <!-- Step 1: Attributes Section -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <span class="w-6 h-6 bg-xieOrange text-white rounded-full flex items-center justify-center text-sm">1</span>
          定義規格屬性
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">先定義規格類型（如：顏色、容量），再為每個類型新增選項</p>

        <!-- Existing Attributes -->
        <div class="space-y-4 mb-4">
          <div v-for="attr in attributes" :key="attr.id" class="border border-gray-200 dark:border-slate-600 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <i class="fas fa-grip-vertical text-gray-400 cursor-move"></i>
                <span class="font-medium text-gray-900 dark:text-white">{{ attr.name }}</span>
                <span class="text-xs text-gray-400">({{ attr.values?.length || 0 }} 個選項)</span>
              </div>
              <button @click="deleteAttribute(attr.id)" class="text-red-500 hover:text-red-700 text-sm">
                <i class="fas fa-trash"></i>
              </button>
            </div>

            <!-- Attribute Values -->
            <div class="flex flex-wrap gap-2 mb-3">
              <span 
                v-for="value in attr.values" 
                :key="value.id"
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-slate-700 rounded-full text-sm"
              >
                <span 
                  v-if="value.color_code" 
                  class="w-4 h-4 rounded-full border border-gray-300"
                  :style="{ backgroundColor: value.color_code }"
                ></span>
                {{ value.value }}
                <button @click="deleteAttributeValue(value.id)" class="text-gray-400 hover:text-red-500">
                  <i class="fas fa-times text-xs"></i>
                </button>
              </span>
            </div>

            <!-- Add Value Form -->
            <div class="flex gap-2">
              <input 
                v-model="newValueInputs[attr.id]"
                type="text" 
                placeholder="新增選項值..."
                class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-xieOrange focus:border-transparent"
                @keyup.enter="addAttributeValue(attr.id)"
              >
              <input 
                v-model="newColorInputs[attr.id]"
                type="color" 
                class="w-10 h-10 rounded cursor-pointer"
                title="選擇顏色（可選）"
              >
              <button 
                @click="addAttributeValue(attr.id)"
                class="px-4 py-2 bg-gray-100 dark:bg-slate-600 text-gray-700 dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-slate-500 transition"
              >
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Add New Attribute -->
        <div class="flex gap-2">
          <input 
            v-model="newAttributeName"
            type="text" 
            placeholder="新增屬性名稱（如：顏色、容量、尺寸）"
            class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-xieOrange focus:border-transparent"
            @keyup.enter="addAttribute"
          >
          <button 
            @click="addAttribute"
            :disabled="!newAttributeName.trim()"
            class="px-6 py-2 bg-xieOrange text-white rounded-lg hover:bg-[#cf8354] transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <i class="fas fa-plus mr-2"></i>新增屬性
          </button>
        </div>
      </div>

      <!-- Step 2: Generate Variants -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <span class="w-6 h-6 bg-xieOrange text-white rounded-full flex items-center justify-center text-sm">2</span>
          產生規格組合
        </h3>

        <div v-if="attributes.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <i class="fas fa-info-circle text-2xl mb-2"></i>
          <p>請先新增至少一個規格屬性</p>
        </div>

        <div v-else>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            目前有 {{ totalCombinations }} 種可能的規格組合
          </p>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">統一價格</label>
              <div class="flex items-center">
                <span class="text-gray-500 mr-2">NT$</span>
                <input 
                  v-model.number="bulkPrice"
                  type="number" 
                  class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                  placeholder="0"
                >
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">統一庫存</label>
              <input 
                v-model.number="bulkStock"
                type="number" 
                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                placeholder="0"
              >
            </div>
          </div>

          <button 
            @click="bulkGenerate"
            :disabled="generating || !canGenerate"
            class="w-full py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <i :class="generating ? 'fas fa-spinner fa-spin' : 'fas fa-magic'"></i>
            {{ generating ? '產生中...' : '批量產生所有組合' }}
          </button>
        </div>
      </div>

      <!-- Step 3: Manage Variants -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="w-6 h-6 bg-xieOrange text-white rounded-full flex items-center justify-center text-sm">3</span>
            管理規格組合
            <span class="text-sm font-normal text-gray-500">({{ variants.length }} 個)</span>
          </h3>

          <!-- Bulk Actions -->
          <div class="flex gap-2" v-if="variants.length > 0">
            <button 
              @click="showBulkPrice = !showBulkPrice"
              class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition"
            >
              <i class="fas fa-dollar-sign mr-1"></i>統一價格
            </button>
            <button 
              @click="showBulkStock = !showBulkStock"
              class="px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition"
            >
              <i class="fas fa-boxes mr-1"></i>統一庫存
            </button>
          </div>
        </div>

        <!-- Bulk Price/Stock Input -->
        <div v-if="showBulkPrice || showBulkStock" class="mb-4 p-4 bg-gray-50 dark:bg-slate-700/50 rounded-lg flex gap-4">
          <div v-if="showBulkPrice" class="flex-1">
            <label class="text-sm text-gray-600 dark:text-gray-300">新價格</label>
            <div class="flex gap-2 mt-1">
              <input v-model.number="bulkPriceUpdate" type="number" class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
              <button @click="applyBulkPrice" class="px-4 py-2 bg-xieOrange text-white rounded-lg hover:bg-[#cf8354]">套用</button>
            </div>
          </div>
          <div v-if="showBulkStock" class="flex-1">
            <label class="text-sm text-gray-600 dark:text-gray-300">新庫存</label>
            <div class="flex gap-2 mt-1">
              <input v-model.number="bulkStockUpdate" type="number" class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
              <button @click="applyBulkStock" class="px-4 py-2 bg-xieOrange text-white rounded-lg hover:bg-[#cf8354]">套用</button>
            </div>
          </div>
        </div>

        <!-- Variants Table -->
        <div v-if="variants.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <i class="fas fa-cube text-2xl mb-2"></i>
          <p>尚無規格組合，請先新增屬性並產生組合</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 dark:border-slate-600">
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-300">SKU</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-300">規格</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-300">價格</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-300">原價</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-300">庫存</th>
                <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-gray-300">預設</th>
                <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-gray-300">啟用</th>
                <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-300">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="variant in variants" 
                :key="variant.id"
                class="border-b border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
              >
                <td class="py-3 px-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ variant.sku }}</td>
                <td class="py-3 px-2 text-gray-900 dark:text-white">{{ variant.options_text }}</td>
                <td class="py-3 px-2">
                  <input 
                    :value="variant.price"
                    @change="updateVariantField(variant.id, 'price', parseInt($event.target.value))"
                    type="number" 
                    class="w-24 px-2 py-1 border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-sm"
                  >
                </td>
                <td class="py-3 px-2">
                  <input 
                    :value="variant.original_price || ''"
                    @change="updateVariantField(variant.id, 'original_price', $event.target.value ? parseInt($event.target.value) : null)"
                    type="number" 
                    placeholder="-"
                    class="w-24 px-2 py-1 border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-sm"
                  >
                </td>
                <td class="py-3 px-2">
                  <input 
                    :value="variant.stock"
                    @change="updateVariantField(variant.id, 'stock', parseInt($event.target.value))"
                    type="number" 
                    class="w-20 px-2 py-1 border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-sm"
                  >
                </td>
                <td class="py-3 px-2 text-center">
                  <input 
                    type="radio" 
                    :checked="variant.is_default"
                    @change="setDefaultVariant(variant.id)"
                    name="default_variant"
                    class="text-xieOrange focus:ring-xieOrange"
                  >
                </td>
                <td class="py-3 px-2 text-center">
                  <button 
                    @click="toggleVariantActive(variant)"
                    :class="variant.is_active ? 'text-emerald-500' : 'text-gray-400'"
                  >
                    <i :class="variant.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                  </button>
                </td>
                <td class="py-3 px-2 text-right">
                  <button @click="deleteVariant(variant.id)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'
import { useToast } from 'vue-toastification'

export default {
  name: 'AdminProductVariants',
  props: {
    productId: {
      type: [Number, String],
      required: true
    }
  },
  emits: ['close'],
  setup() {
    const toast = useToast()
    return { toast }
  },
  data() {
    return {
      loading: true,
      generating: false,
      product: null,
      attributes: [],
      variants: [],
      newAttributeName: '',
      newValueInputs: {},
      newColorInputs: {},
      bulkPrice: 0,
      bulkStock: 10,
      showBulkPrice: false,
      showBulkStock: false,
      bulkPriceUpdate: 0,
      bulkStockUpdate: 0
    }
  },
  computed: {
    totalCombinations() {
      if (this.attributes.length === 0) return 0
      return this.attributes.reduce((total, attr) => {
        const valueCount = attr.values?.length || 0
        return total === 0 ? valueCount : total * (valueCount || 1)
      }, 0)
    },
    canGenerate() {
      return this.attributes.length > 0 && 
             this.attributes.every(a => a.values?.length > 0) &&
             this.bulkPrice > 0
    }
  },
  async mounted() {
    await this.fetchData()
  },
  methods: {
    async fetchData() {
      this.loading = true
      try {
        const res = await api.get(`/admin/products/${this.productId}/variants`)
        this.product = res.data.product
        this.attributes = res.data.attributes || []
        this.variants = res.data.variants || []
        
        // Initialize inputs for each attribute
        this.attributes.forEach(attr => {
          this.newValueInputs[attr.id] = ''
          this.newColorInputs[attr.id] = '#000000'
        })
      } catch (e) {
        console.error('Fetch variants error:', e)
        this.toast.error('載入失敗')
      } finally {
        this.loading = false
      }
    },

    async addAttribute() {
      if (!this.newAttributeName.trim()) return
      try {
        const res = await api.post(`/admin/products/${this.productId}/attributes`, {
          name: this.newAttributeName.trim(),
          display_order: this.attributes.length
        })
        this.attributes.push(res.data)
        this.newValueInputs[res.data.id] = ''
        this.newColorInputs[res.data.id] = '#000000'
        this.newAttributeName = ''
        this.toast.success('屬性已新增')
      } catch (e) {
        console.error('Add attribute error:', e)
        this.toast.error(e.response?.data?.message || '新增失敗')
      }
    },

    async deleteAttribute(attrId) {
      if (!confirm('刪除此屬性將同時刪除所有相關規格組合，確定嗎？')) return
      try {
        await api.delete(`/admin/attributes/${attrId}`)
        this.attributes = this.attributes.filter(a => a.id !== attrId)
        await this.fetchData() // Refresh to get updated variants
        this.toast.success('屬性已刪除')
      } catch (e) {
        console.error('Delete attribute error:', e)
        this.toast.error('刪除失敗')
      }
    },

    async addAttributeValue(attrId) {
      const value = this.newValueInputs[attrId]?.trim()
      if (!value) return
      try {
        const res = await api.post(`/admin/attributes/${attrId}/values`, {
          value: value,
          color_code: this.newColorInputs[attrId] !== '#000000' ? this.newColorInputs[attrId] : null,
          display_order: this.attributes.find(a => a.id === attrId)?.values?.length || 0
        })
        const attr = this.attributes.find(a => a.id === attrId)
        if (attr) {
          if (!attr.values) attr.values = []
          attr.values.push(res.data)
        }
        this.newValueInputs[attrId] = ''
        this.newColorInputs[attrId] = '#000000'
        this.toast.success('選項已新增')
      } catch (e) {
        console.error('Add value error:', e)
        this.toast.error(e.response?.data?.message || '新增失敗')
      }
    },

    async deleteAttributeValue(valueId) {
      try {
        await api.delete(`/admin/attribute-values/${valueId}`)
        this.attributes.forEach(attr => {
          if (attr.values) {
            attr.values = attr.values.filter(v => v.id !== valueId)
          }
        })
        await this.fetchData() // Refresh variants
        this.toast.success('選項已刪除')
      } catch (e) {
        console.error('Delete value error:', e)
        this.toast.error('刪除失敗')
      }
    },

    async bulkGenerate() {
      if (!this.canGenerate) return
      this.generating = true
      try {
        const res = await api.post(`/admin/products/${this.productId}/variants/bulk-generate`, {
          base_price: Math.round(this.bulkPrice),
          base_stock: this.bulkStock
        })
        this.toast.success(res.data.message)
        await this.fetchData()
      } catch (e) {
        console.error('Bulk generate error:', e)
        this.toast.error(e.response?.data?.message || '產生失敗')
      } finally {
        this.generating = false
      }
    },

    async updateVariantField(variantId, field, value) {
      try {
        await api.put(`/admin/variants/${variantId}`, { [field]: value })
        const variant = this.variants.find(v => v.id === variantId)
        if (variant) variant[field] = value
      } catch (e) {
        console.error('Update variant error:', e)
        this.toast.error('更新失敗')
      }
    },

    async setDefaultVariant(variantId) {
      try {
        await api.put(`/admin/variants/${variantId}`, { is_default: true })
        this.variants.forEach(v => { v.is_default = v.id === variantId })
        this.toast.success('已設為預設規格')
      } catch (e) {
        console.error('Set default error:', e)
        this.toast.error('設定失敗')
      }
    },

    async toggleVariantActive(variant) {
      try {
        const newValue = !variant.is_active
        await api.put(`/admin/variants/${variant.id}`, { is_active: newValue })
        variant.is_active = newValue
        this.toast.success(newValue ? '已啟用' : '已停用')
      } catch (e) {
        console.error('Toggle active error:', e)
        this.toast.error('更新失敗')
      }
    },

    async deleteVariant(variantId) {
      if (!confirm('確定要刪除此規格組合嗎？')) return
      try {
        await api.delete(`/admin/variants/${variantId}`)
        this.variants = this.variants.filter(v => v.id !== variantId)
        this.toast.success('規格已刪除')
      } catch (e) {
        console.error('Delete variant error:', e)
        this.toast.error('刪除失敗')
      }
    },

    async applyBulkPrice() {
      if (!this.bulkPriceUpdate) return
      try {
        await api.put(`/admin/products/${this.productId}/variants/bulk-price`, {
          price: Math.round(this.bulkPriceUpdate)
        })
        this.variants.forEach(v => { v.price = Math.round(this.bulkPriceUpdate) })
        this.showBulkPrice = false
        this.toast.success('價格已更新')
      } catch (e) {
        console.error('Bulk price error:', e)
        this.toast.error('更新失敗')
      }
    },

    async applyBulkStock() {
      if (this.bulkStockUpdate === undefined) return
      try {
        await api.put(`/admin/products/${this.productId}/variants/bulk-stock`, {
          stock: this.bulkStockUpdate
        })
        this.variants.forEach(v => { v.stock = this.bulkStockUpdate })
        this.showBulkStock = false
        this.toast.success('庫存已更新')
      } catch (e) {
        console.error('Bulk stock error:', e)
        this.toast.error('更新失敗')
      }
    }
  }
}
</script>
