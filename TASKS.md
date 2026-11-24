# TASKS: koperasi

Aturan: kerjakan SATU task per sesi. Baca REQUIREMENTS.md, ARCHITECTURE.md,
dan RULES.md sebelum mulai. Centang task hanya jika "Selesai jika" terpenuhi.

## Fase 0: Setup
- [X] **T1. Setup backend Laravel**
  Buat proyek Laravel di `backend/`, hubungkan ke MySQL lewat `.env`.
  Selesai jika: `php artisan migrate` berjalan tanpa error.
- [X] **T2. Setup frontend Vue**
  Buat proyek Vue (Vite) di `frontend/`, atur CORS di Laravel.
  Selesai jika: frontend bisa memanggil satu endpoint contoh dari backend.

## Fase 1: Database
- [X] **T3. Migration dan model Product**
  Tabel `products` (name, price, stock unsigned, is_active) sesuai ARCHITECTURE.md.
  Selesai jika: migration berjalan, model Product ada.
- [X] **T4. Migration dan model transaksi**
  Tabel `transactions` dan `transaction_items` dengan foreign key.
  Selesai jika: relasi antar model berfungsi.
- [X] **T5. Seeder data barang**
  Isi 8-10 barang contoh (pulpen, buku, air mineral, dll.).
  Selesai jika: `php artisan db:seed` mengisi tabel products.

## Fase 2: Inti Backend (The 30% Hard Problem)
- [X] **T6. GET /api/products**
  Daftar barang aktif beserta stok.
  Selesai jika: endpoint mengembalikan JSON barang dari database.
- [X] **T7. CheckoutService dan POST /api/checkout**
  `DB::transaction`, `lockForUpdate`, urut by product_id, total dihitung
  di backend, rollback jika satu item gagal.
  Selesai jika: checkout normal menyimpan transaksi dan mengurangi stok.
- [X] **T8. Tes checkout**
  Tes: sukses, item stok habis (semua rollback, stok utuh), barang tidak
  ada, input tidak valid, harga dari klien diabaikan.
  Selesai jika: semua tes lulus.
- [X] **T9. Tes konflik dan simulasi online**
  Buat POST /api/simulate-online-purchase. Uji dua request bersamaan untuk
  stok terakhir (misal dengan skrip dua request paralel).
  Selesai jika: hanya satu berhasil, stok tidak pernah negatif.
- [ ] **T10. Riwayat transaksi**
  GET /api/transactions dan GET /api/transactions/{id}.
  Selesai jika: riwayat tampil beserta detail barang.

## Fase 3: Frontend Inti
- [ ] **T11. Modul API dan halaman daftar barang**
  Semua panggilan API lewat `src/api/`. Tampilkan nama, harga, stok.
  Selesai jika: daftar barang tampil di layar kasir.
- [ ] **T12. Keranjang belanja**
  Tambah, ubah jumlah, hapus item, total otomatis.
  Selesai jika: keranjang bekerja tanpa memanggil backend.
- [ ] **T13. Checkout dan penanganan error**
  Tombol Checkout (nonaktif saat loading), tampilkan pesan error backend,
  muat ulang stok setelah gagal.
  Selesai jika: checkout sukses dan gagal terlihat jelas di UI.
- [ ] **T14. Halaman riwayat**
  Daftar transaksi dan detailnya.
  Selesai jika: riwayat tampil dan sesuai database.

## Fase 4: Kelola Barang (prioritas 2)
- [ ] **T15. Backend kelola barang**
  POST /api/products, PUT /api/products/{id}, POST /api/products/{id}/restock
  (pakai lock, menambah bukan menimpa).
  Selesai jika: ketiga endpoint bekerja dengan validasi.
- [ ] **T16. Tes restock**
  Tes: restock normal, jumlah tidak valid, restock bersamaan dengan penjualan.
  Selesai jika: semua tes lulus.
- [ ] **T17. Halaman Kelola Barang**
  Formulir tambah barang, ubah harga/nama, tombol tambah stok.
  Selesai jika: kasir bisa menambah barang dan stok dari UI.
- [ ] **T18. Nonaktifkan barang (opsional)**
  PATCH /api/products/{id}/deactivate dan tombol di UI.
  Selesai jika: barang nonaktif hilang dari daftar jual, riwayat tetap utuh.

## Fase 5: Finalisasi dan Demo
- [ ] **T19. Skenario demo edge case**
  Siapkan cara memicu skenario 30% saat presentasi (tombol atau skrip):
  checkout 5 barang, barang ke-4 diambil pembeli online di tengah proses.
  Selesai jika: demo bisa diulang dan hasilnya rollback dengan pesan jelas.
- [ ] **T20. README**
  Cara instal, menjalankan, dan menguji.
  Selesai jika: orang lain bisa menjalankan proyek dari README.
- [ ] **T21. Review akhir**
  Cek semua Acceptance Criteria di REQUIREMENTS.md dan commit terakhir.
  Selesai jika: semua kotak tercentang, repo bersih.