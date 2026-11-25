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

  /**
   * Menambah barang baru ke database.
   * Endpoint: POST /api/products
   * @param {{ name: string, price: number, stock: number }} data
   */
  createProduct(data) {
    return apiClient('/products', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  /**
   * Mengubah nama dan/atau harga barang.
   * Endpoint: PUT /api/products/{id}
   * @param {number|string} id
   * @param {{ name?: string, price?: number }} data
   */
  updateProduct(id, data) {
    return apiClient(`/products/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  /**
   * Menambah stok barang (restock).
   * Endpoint: POST /api/products/{id}/restock
   * @param {number|string} id
   * @param {number} quantity
   */
  restockProduct(id, quantity) {
    return apiClient(`/products/${id}/restock`, {
      method: 'POST',
      body: JSON.stringify({ quantity }),
    });
  },

  /**
   * Menonaktifkan barang agar tidak muncul di daftar jual kasir.
   * Endpoint: PATCH /api/products/{id}/deactivate
   * @param {number|string} id
   */
  deactivateProduct(id) {
    return apiClient(`/products/${id}/deactivate`, {
      method: 'PATCH',
    });
  },
};
