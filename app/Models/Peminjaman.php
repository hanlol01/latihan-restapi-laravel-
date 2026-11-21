<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';

    protected $fillable = [
        'buku_id',
        'nama_peminjam',
        'tanggal_pinjam',
        'tanggal_kembali',
    ];

    protected $appends = ['status'];

    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'datetime',
            'tanggal_kembali' => 'datetime',
        ];
    }

    protected function status(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->tanggal_kembali === null ? 'dipinjam' : 'dikembalikan'
        );
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }
}
