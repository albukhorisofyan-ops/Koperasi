<script setup>
import { ref, onMounted } from 'vue'
import { transactionApi } from '@/api'

const transactions = ref([])
const loading = ref(false)
const error = ref(null)

// State Modal Detail Transaksi
const selectedTransaction = ref(null)
const loadingDetail = ref(false)
const detailError = ref(null)

const fetchTransactions = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await transactionApi.getTransactions()
    transactions.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (err) {
    error.value = err.message || 'Gagal memuat riwayat transaksi.'
  } finally {
    loading.value = false
  }
}

/**
 * Mengambil detail transaksi berdasarkan ID dari GET /api/transactions/{id}
 */
const openTransactionDetail = async (id) => {
  loadingDetail.value = true
  detailError.value = null
  selectedTransaction.value = { id } // Buka modal dengan ID sementara loading

  try {
    const data = await transactionApi.getTransactionById(id)
    selectedTransaction.value = data?.data || data
  } catch (err) {
    detailError.value = err.message || 'Gagal mengambil detail transaksi.'
  } finally {
    loadingDetail.value = false
  }
}

const closeDetailModal = () => {
  selectedTransaction.value = null
  detailError.value = null
}

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
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

onMounted(() => {
  fetchTransactions()
})
</script>

<template>
  <div class="history-container">
    <div class="header-row">
      <div>
        <h1 class="page-title">Riwayat Transaksi</h1>
        <p class="page-subtitle">Daftar penjualan barang koperasi yang telah tersimpan di database</p>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" :disabled="loading" @click="fetchTransactions">
          <span v-if="loading">Memuat...</span>
          <span v-else>🔄 Muat Ulang</span>
        </button>
      </div>
    </div>

    <!-- State Loading -->
    <div v-if="loading && transactions.length === 0" class="state-card loading-state">
      <div class="spinner"></div>
      <p>Mengambil data riwayat transaksi dari server...</p>
    </div>

    <!-- State Error -->
    <div v-else-if="error" class="state-card error-state">
      <p class="error-msg">⚠️ {{ error }}</p>
      <button class="btn-retry" @click="fetchTransactions">Coba Lagi</button>
    </div>

    <!-- State Kosong -->
    <div v-else-if="transactions.length === 0" class="state-card empty-state">
      <div class="empty-icon">📜</div>
      <p class="empty-title">Belum ada riwayat transaksi</p>
      <p class="empty-desc">Transaksi yang berhasil di-checkout kasir akan tercatat di sini.</p>
    </div>

    <!-- Tabel Riwayat Transaksi -->
    <div v-else class="table-card">
      <table class="history-table">
        <thead>
          <tr>
            <th>ID Transaksi</th>
            <th>Waktu Transaksi</th>
            <th>Jumlah Item</th>
            <th class="text-right">Total Transaksi</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="trx in transactions"
            :key="trx.id"
            class="history-row"
            @click="openTransactionDetail(trx.id)"
          >
            <td class="font-bold text-primary">#{{ trx.id }}</td>
            <td class="text-muted">{{ formatDate(trx.created_at) }}</td>
            <td>
              <span class="badge-item-count">
                {{ trx.items ? trx.items.length : 0 }} jenis barang
              </span>
            </td>
            <td class="text-right font-bold text-success">
              {{ formatRupiah(trx.total) }}
            </td>
            <td class="text-center">
              <button
                class="btn-detail"
                title="Lihat Rincian Barang"
                @click.stop="openTransactionDetail(trx.id)"
              >
                🔍 Detail
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Detail Transaksi (GET /api/transactions/{id}) -->
    <div
      v-if="selectedTransaction"
      class="modal-backdrop"
      @click.self="closeDetailModal"
    >
      <div class="detail-card">
        <div class="detail-header">
          <div>
            <span class="detail-badge">Detail Penjualan</span>
            <h2 class="detail-title">Transaksi #{{ selectedTransaction.id }}</h2>
            <div class="detail-time">{{ formatDate(selectedTransaction.created_at) }}</div>
          </div>
          <button class="btn-close-modal" title="Tutup" @click="closeDetailModal">✕</button>
        </div>

        <div class="divider"></div>

        <!-- Loading State saat mengambil detail dari API -->
        <div v-if="loadingDetail" class="detail-loading">
          <div class="spinner"></div>
          <p>Memuat rincian barang dari server...</p>
        </div>

        <!-- Error State Detail -->
        <div v-else-if="detailError" class="detail-error">
          <p>⚠️ {{ detailError }}</p>
          <button class="btn-retry-detail" @click="openTransactionDetail(selectedTransaction.id)">
            Coba Lagi
          </button>
        </div>

        <!-- Konten Detail Barang -->
        <div v-else class="detail-content">
          <div class="items-table-wrapper">
            <table class="items-table">
              <thead>
                <tr>
                  <th>Nama Barang</th>
                  <th class="text-center">Kuantitas</th>
                  <th class="text-right">Harga Saat Beli</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedTransaction.items" :key="item.id">
                  <td class="font-semibold">
                    {{ item.product ? item.product.name : `Barang #${item.product_id}` }}
                  </td>
                  <td class="text-center text-muted">{{ item.quantity }} pcs</td>
                  <td class="text-right text-muted">{{ formatRupiah(item.price_at_purchase) }}</td>
                  <td class="text-right font-bold">{{ formatRupiah(item.subtotal) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="divider"></div>

          <div class="detail-total-row">
            <span class="total-label">TOTAL AKHIR:</span>
            <span class="total-amount">{{ formatRupiah(selectedTransaction.total) }}</span>
          </div>

          <div class="detail-actions">
            <button class="btn-modal-close" @click="closeDetailModal">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.history-container {
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

.state-card {
  padding: 56px 24px;
  text-align: center;
  border-radius: 12px;
  background-color: #ffffff;
  border: 1px dashed #d1d5db;
  color: #6b7280;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 12px;
}

.empty-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 6px;
}

.empty-desc {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
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

/* Tabel Riwayat */
.table-card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.history-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.95rem;
}

.history-table th {
  background-color: #f9fafb;
  padding: 14px 18px;
  font-size: 0.825rem;
  font-weight: 700;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.history-table td {
  padding: 16px 18px;
  border-bottom: 1px solid #f3f4f6;
  color: #1f2937;
}

.history-row {
  cursor: pointer;
  transition: background-color 0.15s;
}

.history-row:hover {
  background-color: #f8fafc;
}

.font-bold {
  font-weight: 700;
}

.font-semibold {
  font-weight: 600;
}

.text-primary {
  color: #2563eb;
}

.text-success {
  color: #059669;
}

.text-muted {
  color: #6b7280;
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

.badge-item-count {
  display: inline-block;
  background-color: #f1f5f9;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 9999px;
}

.btn-detail {
  padding: 6px 12px;
  background-color: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
  border-radius: 6px;
  font-size: 0.825rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-detail:hover {
  background-color: #dbeafe;
}

/* Modal Backdrop */
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

.detail-card {
  background-color: #ffffff;
  width: 100%;
  max-width: 600px;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: scaleUp 0.2s ease-out;
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.detail-badge {
  display: inline-block;
  background-color: #dbeafe;
  color: #1e40af;
  font-size: 0.775rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 9999px;
  margin-bottom: 6px;
}

.detail-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: #111827;
  margin: 0;
}

.detail-time {
  font-size: 0.825rem;
  color: #6b7280;
  margin-top: 4px;
}

.btn-close-modal {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px 8px;
  line-height: 1;
}

.btn-close-modal:hover {
  color: #1f2937;
}

.divider {
  border-top: 1px solid #f1f5f9;
  margin: 18px 0;
}

.detail-loading,
.detail-error {
  padding: 32px 16px;
  text-align: center;
  color: #6b7280;
}

.btn-retry-detail {
  margin-top: 8px;
  padding: 6px 14px;
  background-color: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.items-table-wrapper {
  max-height: 280px;
  overflow-y: auto;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.items-table th {
  padding: 8px 12px;
  font-size: 0.775rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  border-bottom: 1px solid #e2e8f0;
}

.items-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f8fafc;
}

.detail-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.15rem;
  margin-bottom: 20px;
}

.total-label {
  font-weight: 800;
  color: #0f172a;
}

.total-amount {
  font-weight: 900;
  color: #059669;
  font-size: 1.35rem;
}

.detail-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-modal-close {
  padding: 10px 24px;
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-modal-close:hover {
  background-color: #1d4ed8;
}

@media (max-width: 640px) {
  .history-table {
    font-size: 0.85rem;
  }
  .history-table th,
  .history-table td {
    padding: 10px 12px;
  }
}
</style>
