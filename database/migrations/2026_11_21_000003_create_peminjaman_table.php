<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buku_id')
                ->index()
                ->constrained('buku')
                ->restrictOnDelete();
            $table->string('nama_peminjam', 150);
            $table->timestamp('tanggal_pinjam');
            $table->timestamp('tanggal_kembali')->nullable();
            $table->timestamps();

            // Index gabungan untuk query daftar buku yang sedang dipinjam urut terbaru.
            $table->index(['tanggal_kembali', 'tanggal_pinjam'], 'peminjaman_status_tanggal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
