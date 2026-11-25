import { apiClient } from './client';

export const transactionApi = {
  /**
   * Mengambil daftar riwayat transaksi.
   * Endpoint: GET /api/transactions
   * @returns {Promise<Array<{ id: number, total: number, created_at: string, items: Array<object> }>>}
   */
  getTransactions() {
    return apiClient('/transactions');
  },

  /**
   * Mengambil detail satu transaksi berdasarkan ID.
   * Endpoint: GET /api/transactions/{id}
   * @param {number|string} id
   * @returns {Promise<{ id: number, total: number, created_at: string, items: Array<object> }>}
   */
  getTransactionById(id) {
    return apiClient(`/transactions/${id}`);
  },
};
