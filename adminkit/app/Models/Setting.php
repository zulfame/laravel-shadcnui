<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Semua setelan sebagai array key => value. */
    public static function values(): array
    {
        return self::pluck('value', 'key')->all();
    }

    /** Simpan banyak setelan sekaligus (upsert). */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
