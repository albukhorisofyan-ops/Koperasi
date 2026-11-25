<script setup>
import { ref, computed, onMounted } from 'vue'
import { productApi } from '@/api'
import { useCart } from '@/composables/useCart'

const products = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')

const {
  cartItems,
  addToCart,
  updateQuantity,
  incrementQuantity,
  decrementQuantity,
  removeFromCart,
  clearCart,
  totalItemsCount,
  totalPrice,
  getItemQuantityInCart,
} = useCart()

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
  <div class="cashier-layout">
    <!-- Kolom Kiri: Katalog Barang -->
    <section class="catalog-section">
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

          <!-- Tombol Tambah ke Keranjang -->
          <button
            class="btn-add-cart"
            :disabled="product.stock === 0 || getItemQuantityInCart(product.id) >= product.stock"
            @click="addToCart(product)"
          >
            <span v-if="product.stock === 0">Stok Habis</span>
            <span v-else-if="getItemQuantityInCart(product.id) >= product.stock">Stok Maksimal</span>
            <span v-else>
              + Tambah <span v-if="getItemQuantityInCart(product.id) > 0">({{ getItemQuantityInCart(product.id) }})</span>
            </span>
          </button>
        </div>
      </div>
    </section>

    <!-- Kolom Kanan: Keranjang Belanja -->
    <aside class="cart-section">
      <div class="cart-card">
        <div class="cart-header">
          <div class="cart-title-row">
            <h2 class="cart-title">🛒 Keranjang Belanja</h2>
            <span v-if="totalItemsCount > 0" class="cart-count-badge">
              {{ totalItemsCount }} item
            </span>
          </div>
          <button
            v-if="cartItems.length > 0"
            class="btn-clear-cart"
            title="Kosongkan Keranjang"
            @click="clearCart"
          >
            Kosongkan
          </button>
        </div>

        <!-- Keranjang Kosong -->
        <div v-if="cartItems.length === 0" class="cart-empty">
          <div class="empty-icon">🛍️</div>
          <p class="empty-text">Keranjang masih kosong</p>
          <p class="empty-hint">Klik tombol "+ Tambah" pada katalog barang di sebelah kiri</p>
        </div>

        <!-- Daftar Item di Keranjang -->
        <div v-else class="cart-content">
          <div class="cart-items-list">
            <div
              v-for="item in cartItems"
              :key="item.product_id"
              class="cart-item"
            >
              <div class="cart-item-header">
                <span class="cart-item-name">{{ item.name }}</span>
                <button
                  class="btn-remove-item"
                  title="Hapus barang"
                  @click="removeFromCart(item.product_id)"
                >
                  ✕
                </button>
              </div>

              <div class="cart-item-details">
                <span class="cart-item-unit-price">{{ formatRupiah(item.price) }} / pcs</span>
                <span class="cart-item-subtotal">{{ formatRupiah(item.price * item.quantity) }}</span>
              </div>

              <div class="cart-item-controls">
                <div class="quantity-controller">
                  <button
                    class="btn-qty"
                    :disabled="item.quantity <= 1"
                    @click="decrementQuantity(item.product_id)"
                  >
                    -
                  </button>
                  <input
                    type="number"
                    min="1"
                    :max="item.stock"
                    :value="item.quantity"
                    class="qty-input"
                    @change="updateQuantity(item.product_id, $event.target.value, item.stock)"
                  />
                  <button
                    class="btn-qty"
                    :disabled="item.quantity >= item.stock"
                    @click="incrementQuantity(item.product_id, item.stock)"
                  >
                    +
                  </button>
                </div>
                <span class="item-stock-limit">maks: {{ item.stock }}</span>
              </div>
            </div>
          </div>

          <!-- Total Ringkasan Otomatis -->
          <div class="cart-summary">
            <div class="summary-row">
              <span class="summary-label">Total Kuantitas:</span>
              <span class="summary-value">{{ totalItemsCount }} pcs</span>
            </div>
            <div class="summary-row total-row">
              <span class="total-label">Total Belanja:</span>
              <span class="total-amount">{{ formatRupiah(totalPrice) }}</span>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.cashier-layout {
  max-width: 1240px;
  margin: 0 auto;
  padding: 24px 16px;
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 24px;
  align-items: start;
}

/* Kolom Kiri: Katalog */
.catalog-section {
  min-width: 0;
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
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.page-subtitle {
  font-size: 0.875rem;
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
  margin-bottom: 20px;
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
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}

.product-card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
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
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 6px;
  line-height: 1.35;
}

.product-price {
  font-size: 1.1rem;
  font-weight: 700;
  color: #059669;
  margin-bottom: 12px;
}

.product-stock-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 10px;
  border-top: 1px solid #f3f4f6;
  margin-bottom: 12px;
}

.stock-label {
  font-size: 0.825rem;
  color: #6b7280;
}

.stock-badge {
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 0.775rem;
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

.btn-add-cart {
  width: 100%;
  padding: 10px;
  background-color: #2563eb;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-add-cart:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-add-cart:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

/* Kolom Kanan: Keranjang */
.cart-section {
  position: sticky;
  top: 80px;
}

.cart-card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.cart-header {
  padding: 16px;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #fafafa;
}

.cart-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cart-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.cart-count-badge {
  background-color: #eff6ff;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
  border: 1px solid #bfdbfe;
}

.btn-clear-cart {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
}

.btn-clear-cart:hover {
  background-color: #fee2e2;
}

.cart-empty {
  padding: 40px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 8px;
}

.empty-text {
  font-weight: 600;
  color: #374151;
  margin: 0 0 4px;
}

.empty-hint {
  font-size: 0.825rem;
  color: #9ca3af;
  margin: 0;
}

.cart-content {
  display: flex;
  flex-direction: column;
}

.cart-items-list {
  max-height: 380px;
  overflow-y: auto;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cart-item {
  background-color: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cart-item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}

.cart-item-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1f2937;
}

.btn-remove-item {
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  font-size: 0.9rem;
  padding: 0 4px;
  line-height: 1;
}

.btn-remove-item:hover {
  color: #ef4444;
}

.cart-item-details {
  display: flex;
  justify-content: space-between;
  font-size: 0.825rem;
}

.cart-item-unit-price {
  color: #6b7280;
}

.cart-item-subtotal {
  font-weight: 700;
  color: #1f2937;
}

.cart-item-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 4px;
}

.quantity-controller {
  display: flex;
  align-items: center;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  overflow: hidden;
  background-color: #ffffff;
}

.btn-qty {
  width: 28px;
  height: 28px;
  border: none;
  background-color: #f3f4f6;
  color: #374151;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}

.btn-qty:hover:not(:disabled) {
  background-color: #e5e7eb;
}

.btn-qty:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.qty-input {
  width: 38px;
  height: 28px;
  border: none;
  text-align: center;
  font-size: 0.875rem;
  font-weight: 600;
  outline: none;
  -moz-appearance: textfield;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.item-stock-limit {
  font-size: 0.75rem;
  color: #9ca3af;
}

.cart-summary {
  padding: 16px;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  margin-bottom: 8px;
  color: #4b5563;
}

.summary-value {
  font-weight: 600;
  color: #1f2937;
}

.total-row {
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px dashed #cbd5e1;
  font-size: 1.1rem;
  margin-bottom: 0;
}

.total-label {
  font-weight: 700;
  color: #0f172a;
}

.total-amount {
  font-weight: 800;
  color: #059669;
  font-size: 1.25rem;
}

/* Responsif untuk layar kecil */
@media (max-width: 900px) {
  .cashier-layout {
    grid-template-columns: 1fr;
  }

  .cart-section {
    position: static;
  }
}
</style>
