<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengembalianTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_mengembalikan_buku_dan_stok_bertambah(): void
    {
        $kategori = Kategori::create(['nama' => 'Pemrograman']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Design Patterns',
            'penulis' => 'GoF',
            'stok' => 1,
        ]);

        $peminjaman = Peminjaman::create([
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Farhan',
            'tanggal_pinjam' => now()->subDay(),
        ]);

        $respons = $this->patchJson("/api/peminjaman/{$peminjaman->id}/kembalikan");

        $respons->assertOk()
            ->assertJsonPath('data.status', 'dikembalikan')
            ->assertJsonPath('data.buku.stok', 2);

        $this->assertNotNull($peminjaman->fresh()->tanggal_kembali);
        $this->assertSame(2, $buku->fresh()->stok);
    }

    public function test_gagal_mengembalikan_buku_yang_sudah_dikembalikan(): void
    {
        $kategori = Kategori::create(['nama' => 'Fiksi']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Negeri 5 Menara',
            'penulis' => 'Ahmad Fuadi',
            'stok' => 3,
        ]);

        $peminjaman = Peminjaman::create([
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Sari',
            'tanggal_pinjam' => now()->subDays(2),
            'tanggal_kembali' => now()->subDay(),
        ]);

        $this->patchJson("/api/peminjaman/{$peminjaman->id}/kembalikan")
            ->assertUnprocessable()
            ->assertJsonPath('pesan', 'Buku ini sudah dikembalikan sebelumnya.');

        $this->assertSame(3, $buku->fresh()->stok);
    }

    public function test_gagal_mengembalikan_jika_peminjaman_tidak_ditemukan(): void
    {
        $this->patchJson('/api/peminjaman/999/kembalikan')
            ->assertNotFound();
    }
}
