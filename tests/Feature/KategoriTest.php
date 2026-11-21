<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_membuat_kategori(): void
    {
        $respons = $this->postJson('/api/kategori', [
            'nama' => 'Pemrograman',
        ]);

        $respons->assertCreated()
            ->assertJsonPath('data.nama', 'Pemrograman');

        $this->assertDatabaseHas('kategori', [
            'nama' => 'Pemrograman',
        ]);
    }

    public function test_gagal_membuat_kategori_duplikat(): void
    {
        Kategori::create(['nama' => 'Fiksi']);

        $respons = $this->postJson('/api/kategori', [
            'nama' => 'Fiksi',
        ]);

        $respons->assertUnprocessable()
            ->assertJsonValidationErrors(['nama']);
    }

    public function test_bisa_melihat_daftar_dan_detail_kategori(): void
    {
        $kategori = Kategori::create(['nama' => 'Sejarah']);

        $this->getJson('/api/kategori')
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Sejarah');

        $this->getJson("/api/kategori/{$kategori->id}")
            ->assertOk()
            ->assertJsonPath('data.nama', 'Sejarah');
    }

    public function test_bisa_mengubah_kategori(): void
    {
        $kategori = Kategori::create(['nama' => 'Sains']);

        $respons = $this->putJson("/api/kategori/{$kategori->id}", [
            'nama' => 'Sains & Teknologi',
        ]);

        $respons->assertOk()
            ->assertJsonPath('data.nama', 'Sains & Teknologi');

        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
            'nama' => 'Sains & Teknologi',
        ]);
    }

    public function test_bisa_menghapus_kategori(): void
    {
        $kategori = Kategori::create(['nama' => 'Komik']);

        $this->deleteJson("/api/kategori/{$kategori->id}")
            ->assertOk();

        $this->assertDatabaseMissing('kategori', [
            'id' => $kategori->id,
        ]);
    }

    public function test_gagal_menghapus_kategori_yang_masih_memiliki_buku(): void
    {
        $kategori = Kategori::create(['nama' => 'Filsafat']);
        Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Dunia Sophie',
            'penulis' => 'Jostein Gaarder',
            'stok' => 2,
        ]);

        $this->deleteJson("/api/kategori/{$kategori->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
        ]);
    }
}
