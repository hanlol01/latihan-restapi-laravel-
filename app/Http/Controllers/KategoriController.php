<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index(): JsonResponse
    {
        $daftarKategori = Kategori::query()
            ->select(['id', 'nama'])
            ->orderBy('nama')
            ->paginate(15);

        return response()->json($daftarKategori);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori,nama'],
        ]);

        $kategori = Kategori::create($data);

        return response()->json([
            'pesan' => 'Kategori berhasil dibuat.',
            'data' => $kategori,
        ], 201);
    }

    public function show(Kategori $kategori): JsonResponse
    {
        return response()->json([
            'data' => $kategori,
        ]);
    }

    public function update(Request $request, Kategori $kategori): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('kategori', 'nama')->ignore($kategori->id)],
        ]);

        $kategori->update($data);

        return response()->json([
            'pesan' => 'Kategori berhasil diperbarui.',
            'data' => $kategori,
        ]);
    }

    public function destroy(Kategori $kategori): JsonResponse
    {
        if ($kategori->buku()->exists()) {
            return response()->json([
                'pesan' => 'Kategori tidak dapat dihapus karena masih memiliki buku.',
            ], 422);
        }

        $kategori->delete();

        return response()->json([
            'pesan' => 'Kategori berhasil dihapus.',
        ]);
    }
}
