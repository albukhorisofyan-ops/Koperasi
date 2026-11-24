# REQUIREMENTS: Koperasi

## 1. Tujuan
Sistem POS (Point of Sales) untuk koperasi kampus. Kasir memproses penjualan,
mengelola barang dan stok sendiri, dan setiap transaksi tidak boleh merusak
data stok.

## 2. Aktor
- **Kasir** (satu-satunya pengguna aplikasi): menjual barang, melihat riwayat,
  mengelola barang dan stok.
- **Pembeli online (simulasi)**: satu endpoint khusus yang mengurangi stok,
  dipakai untuk menciptakan konflik stok saat demo.

Versi ini tanpa login dan tanpa role admin.

## 3. Fitur Inti (prioritas 1)
1. **Daftar barang**: tampilkan nama, harga, dan sisa stok.
2. **Keranjang belanja**: tambah barang, ubah jumlah, hapus item, total otomatis.
3. **Checkout multi-item**: backend menghitung total dari harga di database,
   mengurangi stok semua item, dan menyimpan transaksi. Bersifat all-or-nothing.
4. **Riwayat transaksi**: daftar penjualan beserta detail barang dan total.

## 4. Fitur Kelola Barang (prioritas 2)
1. **Tambah barang baru**: nama, harga, stok awal.
2. **Tambah stok (restock)**: kasir mengirim jumlah tambahan, bukan menimpa stok.
3. **Ubah nama dan harga** barang.
4. **Nonaktifkan barang** (opsional): barang yang pernah terjual tidak dihapus
   permanen, hanya ditandai nonaktif agar riwayat tetap utuh.

## 5. Aturan Bisnis
- Stok tidak boleh negatif.
- Checkout bersifat **all-or-nothing**: jika satu item gagal, seluruh
  transaksi dibatalkan dan stok semua item kembali seperti semula.
- Harga dan total dihitung di backend, bukan dipercaya dari klien.
- Jumlah beli harus bilangan bulat lebih dari 0.
- Harga harus lebih dari 0. Stok awal harus bilangan bulat >= 0.
- Jumlah restock harus bilangan bulat lebih dari 0.
- Restock menambah dari stok saat ini (bukan menimpa), dilakukan dengan
  penguncian baris agar tidak bentrok dengan penjualan.
- Barang nonaktif tidak muncul di daftar jual dan tidak bisa di-checkout.
- Transaksi yang sudah tersimpan tidak bisa diubah atau dihapus.

## 6. The 30% Hard Problem
**Skenario:** kasir checkout 5 jenis barang. Saat proses berjalan, stok
barang ke-4 habis karena dibeli lewat pembelian online.

**Hasil yang diharapkan:**
- Seluruh transaksi di-rollback (stok barang 1-5 tidak berubah).
- API mengembalikan error yang menyebut barang mana yang gagal.
- Server tetap berjalan normal.
- Dua checkout bersamaan untuk stok terakhir: hanya satu yang berhasil.

## 7. Acceptance Criteria
- [ ] Checkout normal: stok berkurang sesuai jumlah, transaksi tercatat.
- [ ] Checkout dengan 1 item stok habis: semua rollback, stok semua item utuh.
- [ ] Dua request bersamaan saat stok sisa 1: satu sukses, satu ditolak.
- [ ] Stok tidak pernah negatif dalam kondisi apa pun.
- [ ] Total transaksi dihitung dari harga di database, bukan dari klien.
- [ ] Input tidak valid ditolak dengan pesan error jelas.
- [ ] Restock bersamaan dengan penjualan tidak menghilangkan salah satunya.
- [ ] Barang yang pernah terjual tidak bisa dihapus permanen.

## 8. Batasan Teknis
- Arsitektur 3-tier: Vue (klien), Laravel REST API (MVC), MySQL (InnoDB).
- Satu backend monolit (bukan microservices).
- Transaksi memakai database transaction dan row locking.

## 9. Di Luar Cakupan
Login dan hak akses, role admin, pembayaran digital, cetak struk fisik,
laporan keuangan lanjutan.