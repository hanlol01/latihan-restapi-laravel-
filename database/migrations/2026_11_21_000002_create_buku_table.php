<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')
                ->index()
                ->constrained('kategori')
                ->restrictOnDelete();
            $table->string('judul', 200)->index();
            $table->string('penulis', 150);
            $table->unsignedSmallInteger('tahun_terbit')->nullable();
            $table->unsignedInteger('stok')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku');
    }
};
