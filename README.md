# Perpustakaan API

REST API sederhana berbasis Laravel untuk mengelola kategori buku, katalog buku, peminjaman, pengembalian, dan daftar buku yang sedang dipinjam (tanpa login/register).

Database bawaannya memakai **SQLite** supaya bisa langsung dicek di lokal tanpa perlu instal atau nyalakan server database tambahan. Kalau ingin pindah ke MySQL atau PostgreSQL, cukup ubah konfigurasi `DB_*` di `.env`.

## Cara Menjalankan di Lokal

Pastikan **PHP 8.3+** (dengan ekstensi `pdo_sqlite`) dan **Composer** sudah terpasang.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Untuk pengguna Windows (PowerShell), ganti perintah `touch` dengan:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

Setelah server jalan, API bisa diakses di `http://127.0.0.1:8000/api`.

## Menjalankan Test

Seluruh alur utama dan kasus pinggiran (stok habis, pengembalian ganda, larangan hapus data yang masih dipakai, dan pengecekan *N+1 query*) sudah dicakup di feature test:

```bash
php artisan test
```

## Catatan Desain & Optimasi Database

- **3 tabel utama:** `kategori`, `buku`, dan `peminjaman` dengan foreign key `restrictOnDelete()` agar kategori yang masih punya buku atau buku yang sudah punya catatan peminjaman tidak terhapus begitu saja.
- **Indexing:** kolom `kategori.nama` (*unique*), `buku.kategori_id`, `buku.judul`, `peminjaman.buku_id`, serta index gabungan `(tanggal_kembali, tanggal_pinjam)` untuk mempercepat query daftar buku yang sedang dipinjam.
- **Anti N+1 & Hemat Kolom:** `Model::preventLazyLoading()` diaktifkan di luar produksi, semua list mengambil kolom yang dibutuhkan saja dengan `with(...)` dan `paginate(15)`.
- **Aman Saat Akses Bersamaan:** peminjaman dan pengembalian dibungkus `DB::transaction()` dengan `lockForUpdate()` supaya stok tidak pernah minus atau bertambah dua kali.
- **Status Tanpa Duplikasi Kolom:** status `dipinjam` / `dikembalikan` dihitung langsung dari kolom `tanggal_kembali` (`null` artinya masih dipinjam).

## Daftar Endpoint

Semua request disarankan menyertakan header `Accept: application/json`.

### 1. Kategori Buku

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api/kategori` | Daftar kategori (paginasi) |
| `POST` | `/api/kategori` | Tambah kategori baru |
| `GET` | `/api/kategori/{id}` | Detail kategori |
| `PUT` / `PATCH` | `/api/kategori/{id}` | Ubah nama kategori |
| `DELETE` | `/api/kategori/{id}` | Hapus kategori (ditolak `422` jika masih punya buku) |

Contoh body `POST /api/kategori`:

```json
{
  "nama": "Hans"
}
```

### 2. Buku

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api/buku` | Daftar buku beserta kategorinya (paginasi) |
| `POST` | `/api/buku` | Tambah buku baru |
| `GET` | `/api/buku/{id}` | Detail buku |
| `PUT` / `PATCH` | `/api/buku/{id}` | Ubah data buku (mendukung update sebagian via `PATCH`) |
| `DELETE` | `/api/buku/{id}` | Hapus buku (ditolak `422` jika sudah punya riwayat peminjaman) |

Contoh body `POST /api/buku`:

```json
{
  "kategori_id": 1,
  "judul": "Kisah Programmer",
  "penulis": "Robert Chaniago",
  "tahun_terbit": 2026,
  "stok": 5
}
```

### 3. Peminjaman, Pengembalian & Daftar Buku yang Dipinjam

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `POST` | `/api/peminjaman` | Pinjam 1 eksemplar buku (stok berkurang 1, ditolak `422` jika stok 0) |
| `PATCH` | `/api/peminjaman/{id}/kembalikan` | Kembalikan buku (stok bertambah 1, ditolak `422` jika sudah dikembalikan) |
| `GET` | `/api/peminjaman` | Daftar buku yang sedang dipinjam. Opsional: `?status=dipinjam\|dikembalikan\|semua` |

Contoh body `POST /api/peminjaman`:

```json
{
  "buku_id": 1,
  "nama_peminjam": "Farhan Maulana"
}
```

Contoh uji cepat lewat `curl`:

```bash
# 1. Buat kategori
curl -X POST http://127.0.0.1:8000/api/kategori \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"nama":"Pemrograman"}'

# 2. Tambah buku
curl -X POST http://127.0.0.1:8000/api/buku \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"kategori_id":1,"judul":"Clean Code","penulis":"Robert C. Martin","tahun_terbit":2008,"stok":3}'

# 3. Pinjam buku
curl -X POST http://127.0.0.1:8000/api/peminjaman \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"buku_id":1,"nama_peminjam":"Andi Pratama"}'

# 4. Lihat daftar buku yang sedang dipinjam
curl -H "Accept: application/json" http://127.0.0.1:8000/api/peminjaman

# 5. Kembalikan buku
curl -X PATCH http://127.0.0.1:8000/api/peminjaman/1/kembalikan \
  -H "Accept: application/json"
```
