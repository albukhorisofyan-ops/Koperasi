# ARCHITECTURE: Koperasi

## 1. Gambaran 3-Tier
```
[Vue (Klien Kasir)]  --HTTP/JSON-->  [Laravel REST API (MVC)]  --SQL-->  [MySQL InnoDB]
```
- **Klien (Vue):** halaman kasir, keranjang, kelola barang, riwayat; validasi
  dasar dan state keranjang.
- **Backend (Laravel):** routing, validasi, aturan bisnis, transaksi database.
- **Database (MySQL):** penyimpanan data dan jaminan ACID.

## 2. Struktur Folder
```
koperasi/
├── backend/    (Laravel)
├── frontend/   (Vue)
├── REQUIREMENTS.md
├── ARCHITECTURE.md
├── RULES.md
└── TASKS.md
```

## 3. Skema Database (ERD)
```mermaid
erDiagram
    products ||--o{ transaction_items : "dijual dalam"
    transactions ||--|{ transaction_items : "berisi"
    products {
        bigint id PK
        string name
        int price
        int stock "unsigned, tidak boleh negatif"
        boolean is_active "false = nonaktif"
        timestamps
    }
    transactions {
        bigint id PK
        int total
        timestamps
    }
    transaction_items {
        bigint id PK
        bigint transaction_id FK
        bigint product_id FK
        int quantity
        int price_at_purchase
        int subtotal
    }
```
Catatan:
- Harga disimpan sebagai bilangan bulat (rupiah), tanpa desimal.
- `price_at_purchase` menyimpan harga saat transaksi, agar riwayat tidak
  berubah jika harga barang diubah nanti.
- `stock` bertipe unsigned sebagai lapisan pengaman kedua di database.
- `is_active` dipakai untuk menonaktifkan barang tanpa menghapus riwayat.
- Foreign key dari `transaction_items.product_id` mencegah penghapusan
  barang yang sudah pernah terjual.

## 4. Endpoint REST API
| Method | Endpoint | Fungsi |
|---|---|---|
| GET | /api/products | Daftar barang aktif dan stok |
| POST | /api/products | Tambah barang baru |
| PUT | /api/products/{id} | Ubah nama atau harga |
| POST | /api/products/{id}/restock | Tambah stok (jumlah tambahan) |
| PATCH | /api/products/{id}/deactivate | Nonaktifkan barang (opsional) |
| POST | /api/checkout | Checkout multi-item (all-or-nothing) |
| GET | /api/transactions | Riwayat transaksi |
| GET | /api/transactions/{id} | Detail satu transaksi |
| POST | /api/simulate-online-purchase | Simulasi pembeli online (demo) |

## 5. Alur Checkout
1. Klien kirim `{ items: [{ product_id, quantity }] }`.
2. Backend validasi input.
3. `DB::transaction`:
   a. Urutkan item berdasarkan `product_id` (hindari deadlock).
   b. Untuk tiap item: ambil produk dengan `lockForUpdate()`.
   c. Jika barang tidak aktif atau stok < quantity: lempar error,
      seluruh transaksi rollback.
   d. Kurangi stok, hitung subtotal dari harga di database.
4. Simpan `transactions` dan `transaction_items`.
5. COMMIT. Kembalikan struk ke klien.

## 6. Alur Restock
1. Klien kirim `{ quantity }` (bilangan bulat > 0).
2. `DB::transaction`: ambil produk dengan `lockForUpdate()`.
3. Tambahkan `quantity` ke stok saat ini, simpan, COMMIT.

## 7. Alur Simulasi Pembeli Online
Endpoint `/api/simulate-online-purchase` menerima `{ product_id, quantity }`
dan mengurangi stok dengan aturan locking yang sama seperti checkout. Dipakai
saat demo untuk menciptakan konflik stok di tengah proses checkout kasir.

## 8. Penanganan Error
| Kondisi | HTTP | Pesan |
|---|---|---|
| Input tidak valid | 422 | Detail kesalahan per field |
| Barang tidak ditemukan | 404 | "Barang tidak ditemukan" |
| Stok tidak cukup | 409 | "Stok {nama barang} tidak cukup" |
| Barang nonaktif | 409 | "Barang {nama barang} tidak tersedia" |
| Error server | 500 | Pesan umum, detail masuk log |

## 9. Struktur Backend (MVC + Service)
- `Controllers`: terima request, panggil service, kembalikan response.
- `Services/CheckoutService`: seluruh logika transaksi dan locking.
- `Services/ProductService`: tambah, ubah, restock, nonaktifkan barang.
- `Models`: Product, Transaction, TransactionItem.
- `Requests`: validasi input.