<script setup>
import { ref, computed, onMounted } from 'vue'
import { productApi, checkoutApi } from '@/api'
import { useCart } from '@/composables/useCart'

const products = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')

// State Checkout
const isCheckingOut = ref(false)
const checkoutError = ref(null)
const checkoutSuccess = ref(null)

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
  if (number === undefined || number === null) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(number)
}

const formatDate = (dateStr) => {
  if (!dateStr) return new Date().toLocaleString('id-ID')
  return new Date(dateStr).toLocaleString('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

/**
 * Memproses checkout transaksi kasir.
 * Menangani state loading, error dari backend, dan muat ulang stok setelah gagal maupun sukses.
 */
const handleCheckout = async () => {
  if (cartItems.value.length === 0 || isCheckingOut.value) return

  isCheckingOut.value = true
  checkoutError.value = null
  checkoutSuccess.value = null

  try {
    const payload = cartItems.value.map((item) => ({
      product_id: item.product_id,
      quantity: item.quantity,
    }))

    const response = await checkoutApi.checkout(payload)

    // Simpan struk transaksi sukses
    checkoutSuccess.value = response.transaction || response.data || response
    clearCart()

    // Muat ulang stok katalog barang
    await fetchProducts()
  } catch (err) {
    // Tampilkan pesan error spesifik dari backend (misal: "Stok Pulpen tidak cukup")
    checkoutError.value = err.message || 'Terjadi kesalahan saat memproses checkout.'

    // Wajib: muat ulang stok setelah gagal agar kasir melihat stok riil terbaru
    await fetchProducts()
  } finally {
    isCheckingOut.value = false
  }
}

const dismissError = () => {
  checkoutError.value = null
}

const closeReceiptModal = () => {
  checkoutSuccess.value = null
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

    <!-- Kolom Kanan: Keranjang Belanja & Checkout -->
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
            :disabled="isCheckingOut"
            title="Kosongkan Keranjang"
            @click="clearCart"
          >
            Kosongkan
          </button>
        </div>

        <!-- Banner Error Checkout -->
        <div v-if="checkoutError" class="checkout-alert-error">
          <div class="alert-icon">⚠️</div>
          <div class="alert-body">
            <div class="alert-title">Checkout Gagal!</div>
            <div class="alert-message">{{ checkoutError }}</div>
            <div class="alert-sub">Stok barang di katalog telah dimuat ulang otomatis.</div>
          </div>
          <button class="btn-alert-close" title="Tutup" @click="dismissError">✕</button>
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
                  :disabled="isCheckingOut"
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
                    :disabled="item.quantity <= 1 || isCheckingOut"
                    @click="decrementQuantity(item.product_id)"
                  >
                    -
                  </button>
                  <input
                    type="number"
                    min="1"
                    :max="item.stock"
                    :disabled="isCheckingOut"
                    :value="item.quantity"
                    class="qty-input"
                    @change="updateQuantity(item.product_id, $event.target.value, item.stock)"
                  />
                  <button
                    class="btn-qty"
                    :disabled="item.quantity >= item.stock || isCheckingOut"
                    @click="incrementQuantity(item.product_id, item.stock)"
                  >
                    +
                  </button>
                </div>
                <span class="item-stock-limit">maks: {{ item.stock }}</span>
              </div>
            </div>
          </div>

          <!-- Total Ringkasan & Tombol Checkout -->
          <div class="cart-summary">
            <div class="summary-row">
              <span class="summary-label">Total Kuantitas:</span>
              <span class="summary-value">{{ totalItemsCount }} pcs</span>
            </div>
            <div class="summary-row total-row">
              <span class="total-label">Total Bayar:</span>
              <span class="total-amount">{{ formatRupiah(totalPrice) }}</span>
            </div>

            <!-- Tombol Checkout -->
            <button
              class="btn-checkout"
              :disabled="cartItems.length === 0 || isCheckingOut"
              @click="handleCheckout"
            >
              <span v-if="isCheckingOut" class="btn-checkout-loading">
                <span class="spinner-small"></span> Memproses Checkout...
              </span>
              <span v-else>
                💳 Proses Checkout ({{ formatRupiah(totalPrice) }})
              </span>
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- Modal Struk Transaksi Sukses -->
    <div v-if="checkoutSuccess" class="modal-backdrop" @click.self="closeReceiptModal">
      <div class="receipt-card">
        <div class="receipt-header">
          <div class="receipt-badge-success">✓ Transaksi Berhasil</div>
          <h2 class="receipt-title">Struk Penjualan Koperasi</h2>
          <div class="receipt-meta">
            <span>No: #{{ checkoutSuccess.id }}</span>
            <span>{{ formatDate(checkoutSuccess.created_at) }}</span>
          </div>
        </div>

        <div class="receipt-divider"></div>

        <div class="receipt-items">
          <div
            v-for="item in checkoutSuccess.items"
            :key="item.id"
            class="receipt-item-row"
          >
            <div class="receipt-item-desc">
              <span class="item-desc-name">{{ item.product ? item.product.name : `Barang #${item.product_id}` }}</span>
              <span class="item-desc-calc">
                {{ item.quantity }} x {{ formatRupiah(item.price_at_purchase) }}
              </span>
            </div>
            <span class="receipt-item-subtotal">
              {{ formatRupiah(item.subtotal) }}
            </span>
          </div>
        </div>

        <div class="receipt-divider"></div>

        <div class="receipt-total-row">
          <span class="receipt-total-label">TOTAL AKHIR:</span>
          <span class="receipt-total-amount">{{ formatRupiah(checkoutSuccess.total) }}</span>
        </div>

        <div class="receipt-footer">
          <p class="receipt-thanks">Terima kasih atas kunjungan Anda!</p>
          <button class="btn-receipt-close" @click="closeReceiptModal">
            Transaksi Baru
          </button>
        </div>
      </div>
    </div>
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

.btn-clear-cart:hover:not(:disabled) {
  background-color: #fee2e2;
}

.btn-clear-cart:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Alert Error Checkout */
.checkout-alert-error {
  background-color: #fef2f2;
  border-bottom: 1px solid #fecaca;
  padding: 12px 14px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.alert-icon {
  font-size: 1.25rem;
  line-height: 1;
}

.alert-body {
  flex: 1;
}

.alert-title {
  font-weight: 700;
  color: #991b1b;
  font-size: 0.875rem;
  margin-bottom: 2px;
}

.alert-message {
  color: #b91c1c;
  font-size: 0.85rem;
  font-weight: 500;
}

.alert-sub {
  color: #6b7280;
  font-size: 0.75rem;
  margin-top: 4px;
}

.btn-alert-close {
  background: none;
  border: none;
  color: #991b1b;
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0 4px;
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
  max-height: 340px;
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

.btn-remove-item:hover:not(:disabled) {
  color: #ef4444;
}

.btn-remove-item:disabled {
  opacity: 0.4;
  cursor: not-allowed;
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

.qty-input:disabled {
  background-color: #f3f4f6;
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
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed #cbd5e1;
  font-size: 1.1rem;
  margin-bottom: 14px;
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

.btn-checkout {
  width: 100%;
  padding: 12px;
  background-color: #059669;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
}

.btn-checkout:hover:not(:disabled) {
  background-color: #047857;
  box-shadow: 0 4px 6px rgba(5, 150, 105, 0.3);
}

.btn-checkout:disabled {
  background-color: #9ca3af;
  box-shadow: none;
  cursor: not-allowed;
  opacity: 0.7;
}

.btn-checkout-loading {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* Modal Struk Sukses */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: 16px;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.receipt-card {
  background-color: #ffffff;
  width: 100%;
  max-width: 420px;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: scaleUp 0.2s ease-out;
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.receipt-header {
  text-align: center;
}

.receipt-badge-success {
  display: inline-block;
  background-color: #d1fae5;
  color: #065f46;
  font-size: 0.825rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 9999px;
  margin-bottom: 8px;
}

.receipt-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #111827;
  margin: 0 0 6px;
}

.receipt-meta {
  display: flex;
  justify-content: center;
  gap: 12px;
  font-size: 0.8rem;
  color: #6b7280;
}

.receipt-divider {
  border-top: 1px dashed #d1d5db;
  margin: 16px 0;
}

.receipt-items {
  max-height: 240px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.receipt-item-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  font-size: 0.875rem;
}

.receipt-item-desc {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-desc-name {
  font-weight: 600;
  color: #1f2937;
}

.item-desc-calc {
  font-size: 0.775rem;
  color: #6b7280;
}

.receipt-item-subtotal {
  font-weight: 700;
  color: #111827;
}

.receipt-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.15rem;
}

.receipt-total-label {
  font-weight: 800;
  color: #0f172a;
}

.receipt-total-amount {
  font-weight: 900;
  color: #059669;
  font-size: 1.35rem;
}

.receipt-footer {
  margin-top: 20px;
  text-align: center;
}

.receipt-thanks {
  font-size: 0.85rem;
  color: #6b7280;
  margin: 0 0 16px;
  font-style: italic;
}

.btn-receipt-close {
  width: 100%;
  padding: 12px;
  background-color: #2563eb;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-receipt-close:hover {
  background-color: #1d4ed8;
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
