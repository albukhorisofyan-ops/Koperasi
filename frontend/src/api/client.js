/**
 * Base API Client wrapper using Fetch API.
 */
const BASE_URL = import.meta.env.VITE_API_URL || '/api';

export async function apiClient(endpoint, options = {}) {
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  const url = `${BASE_URL}${cleanEndpoint}`;

  const defaultHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  const response = await fetch(url, {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    let errorMessage = data?.message || `Request gagal dengan status ${response.status}`;
    // Jika terdapat detail validasi (Laravel 422 errors)
    if (data?.errors && typeof data.errors === 'object') {
      const details = Object.values(data.errors).flat().join(', ');
      if (details) {
        errorMessage = details;
      }
    }
    const error = new Error(errorMessage);
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
}
