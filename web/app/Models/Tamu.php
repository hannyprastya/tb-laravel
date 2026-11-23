<?php

namespace App\Models;

use Database\Factories\TamuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris buku tamu.
 *
 * @property int $id
 * @property string $nama
 * @property \Illuminate\Support\Carbon $created_at
 */
class Tamu extends Model
{
    /** @use HasFactory<TamuFactory> */
    use HasFactory;

    protected $table = 'tamu';

    protected $fillable = ['nama'];
}
