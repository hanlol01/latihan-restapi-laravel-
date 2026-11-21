<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BukuTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_membuat_buku(): void
    {
        $kategori = Kategori::create(['nama' => 'Pemrograman']);

        $respons = $this->postJson('/api/buku', [
            'kategori_id' => $kategori->id,
            'judul' => 'Clean Code',
            'penulis' => 'Robert C. Martin',
            'tahun_terbit' => 2008,
            'stok' => 5,
        ]);

        $respons->assertCreated()
            ->assertJsonPath('data.judul', 'Clean Code')
            ->assertJsonPath('data.kategori.nama', 'Pemrograman')
            ->assertJsonPath('data.stok', 5);

        $this->assertDatabaseHas('buku', [
            'judul' => 'Clean Code',
            'kategori_id' => $kategori->id,
            'stok' => 5,
        ]);
    }

    public function test_gagal_membuat_buku_dengan_kategori_tidak_ada_atau_stok_minus(): void
    {
        $respons = $this->postJson('/api/buku', [
            'kategori_id' => 999,
            'judul' => 'Buku Uji',
            'penulis' => 'Anonim',
            'stok' => -1,
        ]);

        $respons->assertUnprocessable()
            ->assertJsonValidationErrors(['kategori_id', 'stok']);
    }

    public function test_bisa_melihat_daftar_dan_detail_buku(): void
    {
        $kategori = Kategori::create(['nama' => 'Novel']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Laskar Pelangi',
            'penulis' => 'Andrea Hirata',
            'tahun_terbit' => 2005,
            'stok' => 3,
        ]);

        $this->getJson('/api/buku')
            ->assertOk()
            ->assertJsonPath('data.0.judul', 'Laskar Pelangi')
            ->assertJsonPath('data.0.kategori.nama', 'Novel');

        $this->getJson("/api/buku/{$buku->id}")
            ->assertOk()
            ->assertJsonPath('data.judul', 'Laskar Pelangi')
            ->assertJsonPath('data.kategori.nama', 'Novel');
    }

    public function test_bisa_mengubah_buku(): void
    {
        $kategori = Kategori::create(['nama' => 'Teknologi']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Belajar Laravel',
            'penulis' => 'Budi',
            'stok' => 2,
        ]);

        $respons = $this->patchJson("/api/buku/{$buku->id}", [
            'judul' => 'Belajar Laravel Lanjutan',
            'stok' => 7,
        ]);

        $respons->assertOk()
            ->assertJsonPath('data.judul', 'Belajar Laravel Lanjutan')
            ->assertJsonPath('data.stok', 7);
    }

    public function test_bisa_menghapus_buku(): void
    {
        $kategori = Kategori::create(['nama' => 'Bisnis']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Dasar Manajemen',
            'penulis' => 'Siti',
            'stok' => 4,
        ]);

        $this->deleteJson("/api/buku/{$buku->id}")
            ->assertOk();

        $this->assertDatabaseMissing('buku', [
            'id' => $buku->id,
        ]);
    }

    public function test_gagal_menghapus_buku_yang_memiliki_riwayat_peminjaman(): void
    {
        $kategori = Kategori::create(['nama' => 'Sejarah']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Sapiens',
            'penulis' => 'Yuval Noah Harari',
            'stok' => 3,
        ]);

        Peminjaman::create([
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Dedi',
            'tanggal_pinjam' => now(),
        ]);

        $this->deleteJson("/api/buku/{$buku->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('buku', [
            'id' => $buku->id,
        ]);
    }
}
