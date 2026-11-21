<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BukuController extends Controller
{
    public function index(): JsonResponse
    {
        $daftarBuku = Buku::query()
            ->select(['id', 'kategori_id', 'judul', 'penulis', 'tahun_terbit', 'stok'])
            ->with('kategori:id,nama')
            ->orderBy('judul')
            ->paginate(15);

        return response()->json($daftarBuku);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'judul' => ['required', 'string', 'max:200'],
            'penulis' => ['required', 'string', 'max:150'],
            'tahun_terbit' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'stok' => ['required', 'integer', 'min:0'],
        ]);

        $buku = Buku::create($data);
        $buku->load('kategori:id,nama');

        return response()->json([
            'pesan' => 'Buku berhasil dibuat.',
            'data' => $buku,
        ], 201);
    }

    public function show(Buku $buku): JsonResponse
    {
        $buku->load('kategori:id,nama');

        return response()->json([
            'data' => $buku,
        ]);
    }

    public function update(Request $request, Buku $buku): JsonResponse
    {
        $data = $request->validate([
            'kategori_id' => ['sometimes', 'required', 'integer', 'exists:kategori,id'],
            'judul' => ['sometimes', 'required', 'string', 'max:200'],
            'penulis' => ['sometimes', 'required', 'string', 'max:150'],
            'tahun_terbit' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'stok' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        $buku->update($data);
        $buku->load('kategori:id,nama');

        return response()->json([
            'pesan' => 'Buku berhasil diperbarui.',
            'data' => $buku,
        ]);
    }

    public function destroy(Buku $buku): JsonResponse
    {
        $buku->delete();

        return response()->json([
            'pesan' => 'Buku berhasil dihapus.',
        ]);
    }
}
