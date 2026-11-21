<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamanTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_meminjam_buku_dan_stok_berkurang(): void
    {
        $kategori = Kategori::create(['nama' => 'Pemrograman']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Refactoring',
            'penulis' => 'Martin Fowler',
            'stok' => 2,
        ]);

        $respons = $this->postJson('/api/peminjaman', [
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Andi Pratama',
        ]);

        $respons->assertCreated()
            ->assertJsonPath('data.nama_peminjam', 'Andi Pratama')
            ->assertJsonPath('data.status', 'dipinjam')
            ->assertJsonPath('data.tanggal_kembali', null)
            ->assertJsonPath('data.buku.stok', 1);

        $this->assertSame(1, $buku->fresh()->stok);
        $this->assertDatabaseHas('peminjaman', [
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Andi Pratama',
            'tanggal_kembali' => null,
        ]);
    }

    public function test_gagal_meminjam_jika_stok_habis(): void
    {
        $kategori = Kategori::create(['nama' => 'Novel']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Bumi Manusia',
            'penulis' => 'Pramoedya Ananta Toer',
            'stok' => 0,
        ]);

        $respons = $this->postJson('/api/peminjaman', [
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Rina',
        ]);

        $respons->assertUnprocessable()
            ->assertJsonPath('pesan', 'Stok buku sedang habis.');

        $this->assertDatabaseCount('peminjaman', 0);
    }

    public function test_gagal_meminjam_jika_input_tidak_valid(): void
    {
        $respons = $this->postJson('/api/peminjaman', [
            'buku_id' => 999,
            'nama_peminjam' => '',
        ]);

        $respons->assertUnprocessable()
            ->assertJsonValidationErrors(['buku_id', 'nama_peminjam']);
    }
}
