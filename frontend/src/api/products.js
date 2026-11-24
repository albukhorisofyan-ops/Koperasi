import { apiClient } from './client';

export const productApi = {
  /**
   * Mengambil daftar produk aktif beserta sisa stok.
   * Endpoint: GET /api/products
   * @returns {Promise<Array<{ id: number, name: string, price: number, stock: number, is_active: boolean }>>}
   */
  getProducts() {
    return apiClient('/products');
  },
};
