# Perpustakaan API

REST API sederhana untuk mengelola kategori buku, data buku, peminjaman, pengembalian, dan daftar buku yang sedang dipinjam. Dibangun dengan Laravel dan SQLite supaya bisa langsung dijalankan tanpa perlu setup server database dulu.

## Cara Menjalankan di Lokal

Pastikan PHP 8.3+ (dengan ekstensi `pdo_sqlite`) dan Composer sudah terpasang.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

> Untuk Windows (PowerShell), ganti `touch database/database.sqlite` dengan:
> `New-Item -ItemType File -Path database/database.sqlite -Force`

## Menjalankan Test

```bash
php artisan test
```
