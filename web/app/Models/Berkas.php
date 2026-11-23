<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Berkas unggahan milik satu pengguna; isinya di disk s3.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nama_tampil
 * @property string $path
 * @property string $mime
 * @property int $ukuran
 * @property \Illuminate\Support\Carbon $created_at
 */
class Berkas extends Model
{
    protected $table = 'berkas';

    protected $fillable = ['user_id', 'nama_tampil', 'path', 'mime', 'ukuran'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
