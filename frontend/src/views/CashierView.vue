<script setup>
import { ref, computed, onMounted } from 'vue'
import { productApi } from '@/api'

const products = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')

const fetchProducts = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await productApi.getProducts()
    products.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (err) {
    error.value = err.message || 'Gagal memuat daftar barang.'
  } finally {
    loading.value = false
  }
}

const filteredProducts = computed(() => {
  if (!searchQuery.value.trim()) {
    return products.value
  }
  const q = searchQuery.value.toLowerCase().trim()
  return products.value.filter((p) => p.name.toLowerCase().includes(q))
})

const formatRupiah = (number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(number)
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="cashier-container">
    <div class="header-row">
      <div>
        <h1 class="page-title">Daftar Barang Kasir</h1>
        <p class="page-subtitle">Pilih dan pantau stok barang koperasi secara real-time</p>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" :disabled="loading" @click="fetchProducts">
          <span v-if="loading">Memuat...</span>
          <span v-else>🔄 Muat Ulang</span>
        </button>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Cari nama barang..."
        class="search-input"
      />
    </div>

    <!-- State Loading -->
    <div v-if="loading && products.length === 0" class="state-card loading-state">
      <div class="spinner"></div>
      <p>Mengambil data barang dari server...</p>
    </div>

    <!-- State Error -->
    <div v-else-if="error" class="state-card error-state">
      <p class="error-msg">⚠️ {{ error }}</p>
      <button class="btn-retry" @click="fetchProducts">Coba Lagi</button>
    </div>

    <!-- State Kosong -->
    <div v-else-if="filteredProducts.length === 0" class="state-card empty-state">
      <p v-if="searchQuery">Tidak ada barang dengan nama "{{ searchQuery }}".</p>
      <p v-else>Belum ada barang aktif yang tersedia di database.</p>
    </div>

    <!-- Grid Barang -->
    <div v-else class="product-grid">
      <div
        v-for="product in filteredProducts"
        :key="product.id"
        class="product-card"
        :class="{ 'out-of-stock': product.stock === 0 }"
      >
        <div class="product-info">
          <h3 class="product-name">{{ product.name }}</h3>
          <div class="product-price">{{ formatRupiah(product.price) }}</div>
        </div>

        <div class="product-stock-section">
          <span class="stock-label">Sisa Stok:</span>
          <span
            class="stock-badge"
            :class="{
              'badge-danger': product.stock === 0,
              'badge-warning': product.stock > 0 && product.stock <= 5,
              'badge-success': product.stock > 5,
            }"
          >
            {{ product.stock > 0 ? `${product.stock} pcs` : 'Habis' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cashier-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 16px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.page-subtitle {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 4px 0 0;
}

.btn-refresh {
  padding: 8px 16px;
  background-color: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background-color: #e5e7eb;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.search-bar {
  margin-bottom: 24px;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 0.95rem;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.state-card {
  padding: 48px 24px;
  text-align: center;
  border-radius: 12px;
  background-color: #f9fafb;
  border: 1px dashed #d1d5db;
  color: #6b7280;
}

.spinner {
  width: 32px;
  height: 32px;
  margin: 0 auto 12px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-state {
  background-color: #fef2f2;
  border-color: #fecaca;
}

.error-msg {
  color: #dc2626;
  font-weight: 500;
  margin-bottom: 12px;
}

.btn-retry {
  padding: 8px 18px;
  background-color: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}

.product-card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: transform 0.15s, box-shadow 0.15s;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-card.out-of-stock {
  opacity: 0.65;
  background-color: #f9fafb;
}

.product-name {
  font-size: 1.05rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 8px;
  line-height: 1.35;
}

.product-price {
  font-size: 1.15rem;
  font-weight: 700;
  color: #059669;
  margin-bottom: 14px;
}

.product-stock-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 10px;
  border-top: 1px solid #f3f4f6;
}

.stock-label {
  font-size: 0.85rem;
  color: #6b7280;
}

.stock-badge {
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 600;
}

.badge-success {
  background-color: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}
</style>
