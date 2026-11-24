# RULES: Koperasi

File ini wajib dibaca AI sebelum menulis atau mengubah kode.
Rujukan: REQUIREMENTS.md, ARCHITECTURE.md, TASKS.md.

## 1. Aturan Inti: Integritas Data (ACID)
1. Semua operasi yang mengubah stok WAJIB di dalam `DB::transaction()`.
2. Checkout bersifat all-or-nothing. Jika satu item gagal, lempar exception
   agar seluruh transaksi rollback. Jangan pernah menangkap error lalu
   melanjutkan dengan transaksi sebagian.
3. Sebelum membaca stok untuk diubah, ambil barisnya dengan `lockForUpdate()`
   di dalam transaksi. Dilarang pola "baca stok, cek di PHP, lalu update"
   tanpa lock.
4. Urutkan item berdasarkan `product_id` sebelum dikunci (cegah deadlock).
5. Stok tidak boleh negatif. Cek di kode DAN pasang pengaman di database
   (kolom unsigned atau CHECK constraint).
6. Restock menambah stok saat ini (`stock + n`), tidak boleh menimpa dengan
   nilai dari klien.
7. Tabel memakai mesin InnoDB.
8. Transaksi yang sudah tersimpan tidak boleh diubah atau dihapus.

## 2. Aturan Keamanan dan Validasi
1. Jangan percaya data dari klien. Harga, subtotal, dan total SELALU dihitung
   ulang di backend dari data database.
2. Validasi semua input memakai Form Request Laravel (jumlah bulat > 0,
   harga > 0, product_id harus ada).
3. Gunakan Eloquent atau query binding. Dilarang menyusun SQL dari string
   input.
4. Jangan tampilkan stack trace atau detail error internal ke klien. Detail
   dicatat di log.
5. Konfigurasi dan kredensial hanya di `.env`. Jangan commit `.env`.

## 3. Aturan Arsitektur
1. Struktur MVC + Service. Controller tipis: terima request, panggil service,
   kembalikan response.
2. Logika bisnis dan transaksi hanya di Service (`CheckoutService`,
   `ProductService`), bukan di Controller atau Model.
3. Satu