<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'dipinjam');

        if (! in_array($status, ['dipinjam', 'dikembalikan', 'semua'], true)) {
            return response()->json([
                'pesan' => 'Parameter status hanya menerima: dipinjam, dikembalikan, atau semua.',
            ], 422);
        }

        $daftarPeminjaman = Peminjaman::query()
            ->select(['id', 'buku_id', 'nama_peminjam', 'tanggal_pinjam', 'tanggal_kembali'])
            ->with([
                'buku:id,kategori_id,judul,penulis,stok',
                'buku.kategori:id,nama',
            ])
            ->when($status === 'dipinjam', fn ($query) => $query->whereNull('tanggal_kembali'))
            ->when($status === 'dikembalikan', fn ($query) => $query->whereNotNull('tanggal_kembali'))
            ->latest('tanggal_pinjam')
            ->paginate(15);

        return response()->json($daftarPeminjaman);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'buku_id' => ['required', 'integer', 'exists:buku,id'],
            'nama_peminjam' => ['required', 'string', 'max:150'],
        ]);

        $peminjaman = DB::transaction(function () use ($data) {
            // Kunci baris buku agar stok tidak minus saat ada peminjaman bersamaan.
            $buku = Buku::whereKey($data['buku_id'])->lockForUpdate()->firstOrFail();

            if ($buku->stok < 1) {
                return null;
            }

            $buku->decrement('stok');

            return Peminjaman::create([
                'buku_id' => $buku->id,
                'nama_peminjam' => $data['nama_peminjam'],
                'tanggal_pinjam' => now(),
            ]);
        });

        if ($peminjaman === null) {
            return response()->json([
                'pesan' => 'Stok buku sedang habis.',
            ], 422);
        }

        $peminjaman->load([
            'buku:id,kategori_id,judul,penulis,stok',
            'buku.kategori:id,nama',
        ]);

        return response()->json([
            'pesan' => 'Buku berhasil dipinjam.',
            'data' => $peminjaman,
        ], 201);
    }

    public function kembalikan(int $id): JsonResponse
    {
        $hasil = DB::transaction(function () use ($id) {
            // Kunci baris peminjaman agar pengembalian ganda tidak menambah stok dua kali.
            $peminjaman = Peminjaman::whereKey($id)->lockForUpdate()->first();

            if (! $peminjaman) {
                return 'tidak_ditemukan';
            }

            if ($peminjaman->tanggal_kembali !== null) {
                return 'sudah_dikembalikan';
            }

            $peminjaman->update([
                'tanggal_kembali' => now(),
            ]);

            Buku::whereKey($peminjaman->buku_id)->increment('stok');

            return $peminjaman;
        });

        if ($hasil === 'tidak_ditemukan') {
            return response()->json([
                'pesan' => 'Data peminjaman tidak ditemukan.',
            ], 404);
        }

        if ($hasil === 'sudah_dikembalikan') {
            return response()->json([
                'pesan' => 'Buku ini sudah dikembalikan sebelumnya.',
            ], 422);
        }

        $hasil->load([
            'buku:id,kategori_id,judul,penulis,stok',
            'buku.kategori:id,nama',
        ]);

        return response()->json([
            'pesan' => 'Buku berhasil dikembalikan.',
            'data' => $hasil,
        ]);
    }
}
