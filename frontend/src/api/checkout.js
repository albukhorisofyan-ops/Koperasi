import { apiClient } from './client';

export const checkoutApi = {
  /**
   * Melakukan checkout multi-item ke backend.
   * Endpoint: POST /api/checkout
   * @param {Array<{ product_id: number, quantity: number }>} items
   * @returns {Promise<{ message: string, data: object, transaction: object }>}
   */
  checkout(items) {
    return apiClient('/checkout', {
      method: 'POST',
      body: JSON.stringify({ items }),
    });
  },
};
