import { ref, computed } from 'vue'

const cartItems = ref([])

export function useCart() {
  /**
   * Menambahkan barang ke keranjang atau menambah jumlah jika sudah ada.
   * Dibatasi oleh sisa stok barang.
   */
  const addToCart = (product) => {
    if (!product || product.stock <= 0) return

    const existing = cartItems.value.find((item) => item.product_id === product.id)
    if (existing) {
      if (existing.quantity < product.stock) {
        existing.quantity += 1
      }
    } else {
      cartItems.value.push({
        product_id: product.id,
        name: product.name,
        price: product.price,
        stock: product.stock,
        quantity: 1,
      })
    }
  }

  /**
   * Mengubah jumlah item di keranjang secara langsung (misal dari input teks).
   */
  const updateQuantity = (productId, newQuantity, maxStock) => {
    const item = cartItems.value.find((i) => i.product_id === productId)
    if (!item) return

    const parsed = parseInt(newQuantity, 10)
    if (isNaN(parsed) || parsed < 1) {
      item.quantity = 1
      return
    }

    const limit = maxStock !== undefined ? maxStock : item.stock
    if (parsed > limit) {
      item.quantity = limit
    } else {
      item.quantity = parsed
    }
  }

  /**
   * Menambah jumlah item (+1) tanpa melebihi stok yang tersedia.
   */
  const incrementQuantity = (productId, maxStock) => {
    const item = cartItems.value.find((i) => i.product_id === productId)
    if (!item) return

    const limit = maxStock !== undefined ? maxStock : item.stock
    if (item.quantity < limit) {
      item.quantity += 1
    }
  }

  /**
   * Mengurangi jumlah item (-1). Jika jumlah mencapai 0, item dihapus dari keranjang.
   */
  const decrementQuantity = (productId) => {
    const item = cartItems.value.find((i) => i.product_id === productId)
    if (!item) return

    if (item.quantity > 1) {
      item.quantity -= 1
    } else {
      removeFromCart(productId)
    }
  }

  /**
   * Menghapus item dari keranjang.
   */
  const removeFromCart = (productId) => {
    cartItems.value = cartItems.value.filter((i) => i.product_id !== productId)
  }

  /**
   * Mengosongkan seluruh isi keranjang.
   */
  const clearCart = () => {
    cartItems.value = []
  }

  /**
   * Menghitung total banyaknya item/unit di keranjang secara otomatis.
   */
  const totalItemsCount = computed(() => {
    return cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  /**
   * Menghitung total harga belanja secara otomatis (tanpa memanggil backend).
   */
  const totalPrice = computed(() => {
    return cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  })

  /**
   * Helper untuk mendapatkan kuantitas suatu produk yang sudah ada di keranjang.
   */
  const getItemQuantityInCart = (productId) => {
    const found = cartItems.value.find((i) => i.product_id === productId)
    return found ? found.quantity : 0
  }

  return {
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
  }
}
