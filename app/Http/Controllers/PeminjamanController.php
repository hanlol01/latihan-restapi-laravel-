<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
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
}
