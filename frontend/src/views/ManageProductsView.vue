<script setup>
import { ref, computed, onMounted } from 'vue'
import { productApi } from '@/api'

const products = ref([])
const loading = ref(false)
const listError = ref(null)
const successNotice = ref(null)
const searchQuery = ref('')

// Form Tambah Barang
const newProduct = ref({
  name: '',
  price: '',
  stock: '',
})
const isSubmittingNew = ref(false)
const createError = ref(null)

// Modal Ubah Barang (Edit)
const editModal = ref({
  open: false,
  id: null,
  name: '',
  price: '',
  loading: false,
  error: null,
})

// Modal Restock (Tambah Stok)
const restockModal = ref({
  open: false,
  id: null,
  name: '',
  currentStock: 0,
  quantity: 1,
  loading: false,
  error: null,
})

const fetchProducts = async () => {
  loading.value = true
  listError.value = null
  try {
    const data = await productApi.getProducts()
    products.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (err) {
    listError.value = err.message || 'Gagal memuat daftar barang.'
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

const showSuccess = (msg) => {
  successNotice.value = msg
  setTimeout(() => {
    if (successNotice.value === msg) {
      successNotice.value = null
    }
  }, 4000)
}

/**
 * Handle submit tambah barang baru
 */
const handleCreateProduct = async () => {
  createError.value = null

  if (!newProduct.value.name.trim()) {
    createError.value = 'Nama barang harus diisi.'
    return
  }
  if (!newProduct.value.price || parseInt(newProduct.value.price, 10) <= 0) {
    createError.value = 'Harga harus berupa bilangan bulat lebih dari 0.'
    return
  }
  if (newProduct.value.stock === '' || parseInt(newProduct.value.stock, 10) < 0) {
    createError.value = 'Stok awal harus bilangan bulat >= 0.'
    return
  }

  isSubmittingNew.value = true
  try {
    await productApi.createProduct({
      name: newProduct.value.name.trim(),
      price: parseInt(newProduct.value.price, 10),
      stock: parseInt(newProduct.value.stock, 10),
    })

    showSuccess(`Barang "${newProduct.value.name}" berhasil ditambahkan!`)
    newProduct.value = { name: '', price: '', stock: '' }

    // Muat ulang daftar barang
    await fetchProducts()
  } catch (err) {
    createError.value = err.message || 'Gagal menambahkan barang.'
  } finally {
    isSubmittingNew.value = false
  }
}

/**
 * Buka modal ubah nama/harga
 */
const openEditModal = (product) => {
  editModal.value = {
    open: true,
    id: product.id,
    name: product.name,
    price: product.price,
    loading: false,
    error: null,
  }
}

const closeEditModal = () => {
  editModal.value.open = false
}

/**
 * Handle submit simpan perubahan nama/harga
 */
const handleUpdateProduct = async () => {
  editModal.value.error = null

  if (!editModal.value.name.trim()) {
    editModal.value.error = 'Nama barang tidak boleh kosong.'
    return
  }
  if (!editModal.value.price || parseInt(editModal.value.price, 10) <= 0) {
    editModal.value.error = 'Harga harus lebih dari 0.'
    return
  }

  editModal.value.loading = true
  try {
    await productApi.updateProduct(editModal.value.id, {
      name: editModal.value.name.trim(),
      price: parseInt(editModal.value.price, 10),
    })

    showSuccess(`Data barang "${editModal.value.name}" berhasil diperbarui!`)
    closeEditModal()

    // Muat ulang daftar barang
    await fetchProducts()
  } catch (err) {
    editModal.value.error = err.message || 'Gagal memperbarui barang.'
  } finally {
    editModal.value.loading = false
  }
}

/**
 * Buka modal restock
 */
const openRestockModal = (product) => {
  restockModal.value = {
    open: true,
    id: product.id,
    name: product.name,
    currentStock: product.stock,
    quantity: 10,
    loading: false,
    error: null,
  }
}

const closeRestockModal = () => {
  restockModal.value.open = false
}

/**
 * Handle submit tambah stok (restock)
 */
const handleRestockProduct = async () => {
  restockModal.value.error = null

  const qty = parseInt(restockModal.value.quantity, 10)
  if (isNaN(qty) || qty <= 0) {
    restockModal.value.error = 'Jumlah tambahan restock harus bilangan bulat > 0.'
    return
  }

  restockModal.value.loading = true
  try {
    await productApi.restockProduct(restockModal.value.id, qty)

    showSuccess(`Stok "${restockModal.value.name}" berhasil ditambah sebanyak +${qty} pcs!`)
    closeRestockModal()

    // Muat ulang daftar barang
    await fetchProducts()
  } catch (err) {
    restockModal.value.error = err.message || 'Gagal menambahkan stok.'
  } finally {
    restockModal.value.loading = false
  }
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="manage-container">
    <div class="header-row">
      <div>
        <h1 class="page-title">Kelola Barang & Stok</h1>
        <p class="page-subtitle">Tambah barang baru, perbarui nama/harga, atau tambah stok barang (restock)</p>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" :disabled="loading" @click="fetchProducts">
          <span v-if="loading">Memuat...</span>
          <span v-else>🔄 Muat Ulang</span>
        </button>
      </div>
    </div>

    <!-- Alert Sukses Toast/Banner -->
    <div v-if="successNotice" class="alert-banner alert-success">
      <span>✓ {{ successNotice }}</span>
      <button class="btn-close-alert" @click="successNotice = null">✕</button>
    </div>

    <!-- Formulir Tambah Barang Baru -->
    <div class="card form-card">
      <h2 class="card-title">➕ Tambah Barang Baru</h2>
      <p class="card-subtitle">Masukkan rincian nama, harga satuan, dan stok awal barang</p>

      <div v-if="createError" class="alert-banner alert-danger">
        <span>⚠️ {{ createError }}</span>
        <button class="btn-close-alert" @click="createError = null">✕</button>
      </div>

      <form class="create-form" @submit.prevent="handleCreateProduct">
        <div class="form-group form-group-name">
          <label class="form-label">Nama Barang</label>
          <input
            v-model="newProduct.name"
            type="text"
            placeholder="Contoh: Pulpen Gel 0.5mm"
            class="form-input"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Harga Satuan (Rp)</label>
          <input
            v-model="newProduct.price"
            type="number"
            min="1"
            placeholder="Contoh: 3500"
            class="form-input"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Stok Awal (pcs)</label>
          <input
            v-model="newProduct.stock"
            type="number"
            min="0"
            placeholder="Contoh: 50"
            class="form-input"
            required
          />
        </div>

        <div class="form-group form-group-btn">
          <label class="form-label invisible-label">Aksi</label>
          <button
            type="submit"
            class="btn-submit-product"
            :disabled="isSubmittingNew"
          >
            <span v-if="isSubmittingNew">Menyimpan...</span>
            <span v-else>+ Tambah Barang</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Tabel Daftar Barang -->
    <div class="card table-card">
      <div class="table-card-header">
        <h2 class="card-title">📦 Daftar Inventaris Barang ({{ products.length }} produk)</h2>
        <div class="search-box">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama barang..."
            class="search-input-small"
          />
        </div>
      </div>

      <!-- State Loading -->
      <div v-if="loading && products.length === 0" class="state-card loading-state">
        <div class="spinner"></div>
        <p>Mengambil data barang...</p>
      </div>

      <!-- State Error -->
      <div v-else-if="listError" class="state-card error-state">
        <p class="error-msg">⚠️ {{ listError }}</p>
        <button class="btn-retry" @click="fetchProducts">Coba Lagi</button>
      </div>

      <!-- State Kosong -->
      <div v-else-if="filteredProducts.length === 0" class="state-card empty-state">
        <p v-if="searchQuery">Tidak ada barang yang cocok dengan "{{ searchQuery }}".</p>
        <p v-else>Belum ada barang aktif di database.</p>
      </div>

      <!-- Tabel Data -->
      <div v-else class="table-responsive">
        <table class="product-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama Barang</th>
              <th>Harga Satuan</th>
              <th>Sisa Stok</th>
              <th class="text-center">Aksi Pengelolaan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in filteredProducts" :key="product.id">
              <td class="font-mono text-muted">#{{ product.id }}</td>
              <td class="font-bold text-dark">{{ product.name }}</td>
              <td class="font-semibold text-success">{{ formatRupiah(product.price) }}</td>
              <td>
                <span
                  class="stock-badge"
                  :class="{
                    'badge-danger': product.stock === 0,
                    'badge-warning': product.stock > 0 && product.stock <= 5,
                    'badge-success': product.stock > 5,
                  }"
                >
                  {{ product.stock }} pcs
                </span>
              </td>
              <td class="text-center actions-cell">
                <button
                  class="btn-action btn-edit"
                  title="Ubah Nama atau Harga"
                  @click="openEditModal(product)"
                >
                  ✏️ Ubah
                </button>
                <button
                  class="btn-action btn-restock"
                  title="Tambah Stok (Restock)"
                  @click="openRestockModal(product)"
                >
                  ➕ Tambah Stok
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Ubah Nama / Harga -->
    <div v-if="editModal.open" class="modal-backdrop" @click.self="closeEditModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title">✏️ Ubah Nama / Harga Barang</h3>
          <button class="btn-close-modal" @click="closeEditModal">✕</button>
        </div>

        <div v-if="editModal.error" class="alert-banner alert-danger">
          <span>⚠️ {{ editModal.error }}</span>
        </div>

        <form @submit.prevent="handleUpdateProduct">
          <div class="modal-body">
            <div class="form-group mb-4">
              <label class="form-label">Nama Barang</label>
              <input
                v-model="editModal.name"
                type="text"
                class="form-input"
                required
              />
            </div>
            <div class="form-group">
              <label class="form-label">Harga Satuan (Rp)</label>
              <input
                v-model="editModal.price"
                type="number"
                min="1"
                class="form-input"
                required
              />
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn-secondary"
              :disabled="editModal.loading"
              @click="closeEditModal"
            >
              Batal
            </button>
            <button
              type="submit"
              class="btn-primary"
              :disabled="editModal.loading"
            >
              <span v-if="editModal.loading">Menyimpan...</span>
              <span v-else>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Tambah Stok (Restock) -->
    <div v-if="restockModal.open" class="modal-backdrop" @click.self="closeRestockModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title">📦 Tambah Stok (Restock)</h3>
          <button class="btn-close-modal" @click="closeRestockModal">✕</button>
        </div>

        <div v-if="restockModal.error" class="alert-banner alert-danger">
          <span>⚠️ {{ restockModal.error }}</span>
        </div>

        <form @submit.prevent="handleRestockProduct">
          <div class="modal-body">
            <div class="restock-product-info">
              <div class="info-name">{{ restockModal.name }}</div>
              <div class="info-stock">Sisa stok saat ini: <strong>{{ restockModal.currentStock }} pcs</strong></div>
            </div>

            <div class="form-group mb-3">
              <label class="form-label">Jumlah Tambahan Restock (pcs)</label>
              <input
                v-model="restockModal.quantity"
                type="number"
                min="1"
                class="form-input font-bold"
                placeholder="Jumlah tambahan, misal: 10"
                required
              />
              <span class="form-hint">Kuantitas ini akan ditambahkan ke stok saat ini (bukan menimpa).</span>
            </div>

            <div class="restock-preview">
              Stok setelah restock:
              <strong class="text-success">
                {{ restockModal.currentStock + (parseInt(restockModal.quantity, 10) || 0) }} pcs
              </strong>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn-secondary"
              :disabled="restockModal.loading"
              @click="closeRestockModal"
            >
              Batal
            </button>
            <button
              type="submit"
              class="btn-primary btn-success-action"
              :disabled="restockModal.loading"
            >
              <span v-if="restockModal.loading">Menambahkan...</span>
              <span v-else>➕ Tambah Stok</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.manage-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 16px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
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

/* Alert Banners */
.alert-banner {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
  font-weight: 500;
}

.alert-success {
  background-color: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.alert-danger {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.btn-close-alert {
  background: none;
  border: none;
  color: inherit;
  font-size: 1rem;
  cursor: pointer;
  padding: 0 4px;
}

/* Cards */
.card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 24px;
}

.form-card {
  padding: 24px;
}

.card-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px;
}

.card-subtitle {
  font-size: 0.85rem;
  color: #6b7280;
  margin: 0 0 20px;
}

/* Form */
.create-form {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr auto;
  gap: 16px;
  align-items: end;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 0.825rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.invisible-label {
  visibility: hidden;
}

.form-input {
  padding: 10px 14px;
  font-size: 0.9rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 4px;
}

.btn-submit-product {
  padding: 10px 20px;
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.15s;
}

.btn-submit-product:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-submit-product:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

/* Table Card */
.table-card-header {
  padding: 20px 24px;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.search-input-small {
  padding: 8px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.85rem;
  width: 220px;
  outline: none;
}

.search-input-small:focus {
  border-color: #3b82f6;
}

.table-responsive {
  overflow-x: auto;
}

.product-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.925rem;
}

.product-table th {
  background-color: #f9fafb;
  padding: 12px 20px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.product-table td {
  padding: 14px 20px;
  border-bottom: 1px solid #f3f4f6;
}

.actions-cell {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.btn-action {
  padding: 6px 12px;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.15s;
}

.btn-edit {
  background-color: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}

.btn-edit:hover {
  background-color: #dcfce7;
}

.btn-restock {
  background-color: #eff6ff;
  border-color: #bfdbfe;
  color: #1e40af;
}

.btn-restock:hover {
  background-color: #dbeafe;
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

.state-card {
  padding: 48px 24px;
  text-align: center;
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
  to { transform: rotate(360deg); }
}

.btn-retry {
  padding: 8px 18px;
  background-color: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  margin-top: 8px;
}

/* Modals */
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
}

.modal-card {
  background-color: #ffffff;
  width: 100%;
  max-width: 480px;
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.btn-close-modal {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #9ca3af;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  margin-bottom: 20px;
}

.mb-3 { margin-bottom: 12px; }
.mb-4 { margin-bottom: 16px; }

.restock-product-info {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 12px 14px;
  border-radius: 8px;
  margin-bottom: 16px;
}

.info-name {
  font-weight: 700;
  font-size: 0.95rem;
  color: #0f172a;
}

.info-stock {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 2px;
}

.restock-preview {
  background-color: #f0fdf4;
  border: 1px dashed #86efac;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #166534;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-secondary {
  padding: 10px 18px;
  background-color: #f3f4f6;
  border: 1px solid #d1d5db;
  color: #374151;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  padding: 10px 20px;
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-success-action {
  background-color: #059669;
}

.btn-success-action:hover:not(:disabled) {
  background-color: #047857;
}

.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-mono { font-family: monospace; }
.text-muted { color: #6b7280; }
.text-dark { color: #111827; }
.text-success { color: #059669; }
.text-center { text-align: center; }

@media (max-width: 800px) {
  .create-form {
    grid-template-columns: 1fr;
  }
  .invisible-label {
    display: none;
  }
}
</style>
