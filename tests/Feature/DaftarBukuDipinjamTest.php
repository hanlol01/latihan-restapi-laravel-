<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DaftarBukuDipinjamTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_hanya_menampilkan_buku_yang_sedang_dipinjam(): void
    {
        $kategori = Kategori::create(['nama' => 'Pemrograman']);
        $bukuPertama = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Clean Architecture',
            'penulis' => 'Robert C. Martin',
            'stok' => 2,
        ]);
        $bukuKedua = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Pragmatic Programmer',
            'penulis' => 'David Thomas',
            'stok' => 4,
        ]);

        Peminjaman::create([
            'buku_id' => $bukuPertama->id,
            'nama_peminjam' => 'Andi',
            'tanggal_pinjam' => now()->subHours(2),
        ]);

        Peminjaman::create([
            'buku_id' => $bukuKedua->id,
            'nama_peminjam' => 'Budi',
            'tanggal_pinjam' => now()->subHour(),
            'tanggal_kembali' => now(),
        ]);

        $respons = $this->getJson('/api/peminjaman');

        $respons->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_peminjam', 'Andi')
            ->assertJsonPath('data.0.status', 'dipinjam')
            ->assertJsonPath('data.0.buku.judul', 'Clean Architecture')
            ->assertJsonPath('data.0.buku.kategori.nama', 'Pemrograman');
    }

    public function test_bisa_filter_status_dikembalikan_dan_semua(): void
    {
        $kategori = Kategori::create(['nama' => 'Novel']);
        $buku = Buku::create([
            'kategori_id' => $kategori->id,
            'judul' => 'Pulang',
            'penulis' => 'Tere Liye',
            'stok' => 3,
        ]);

        Peminjaman::create([
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Citra',
            'tanggal_pinjam' => now()->subDay(),
        ]);

        Peminjaman::create([
            'buku_id' => $buku->id,
            'nama_peminjam' => 'Dewi',
            'tanggal_pinjam' => now()->subDays(3),
            'tanggal_kembali' => now()->subDays(2),
        ]);

        $this->getJson('/api/peminjaman?status=dikembalikan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_peminjam', 'Dewi')
            ->assertJsonPath('data.0.status', 'dikembalikan');

        $this->getJson('/api/peminjaman?status=semua')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_gagal_jika_parameter_status_tidak_dikenal(): void
    {
        $this->getJson('/api/peminjaman?status=hilang')
            ->assertUnprocessable();
    }

    public function test_daftar_peminjaman_tidak_terkena_n_plus_1_query(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $kategori = Kategori::create(['nama' => "Kategori {$i}"]);
            $buku = Buku::create([
                'kategori_id' => $kategori->id,
                'judul' => "Buku {$i}",
                'penulis' => "Penulis {$i}",
                'stok' => 2,
            ]);

            Peminjaman::create([
                'buku_id' => $buku->id,
                'nama_peminjam' => "Peminjam {$i}",
                'tanggal_pinjam' => now()->subMinutes($i),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/peminjaman')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $jumlahQuery = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Maksimal 4 query: count pagination, daftar peminjaman, eager load buku, eager load kategori.
        $this->assertLessThanOrEqual(4, $jumlahQuery);
    }
}
