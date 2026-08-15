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

    /**
     * Simpan banyak setelan sekaligus (upsert) dan kembalikan diff-nya
     * untuk audit trail. Nilai `null` pada kunci non-aset disimpan sebagai
     * string kosong agar "sengaja dikosongkan" tidak jatuh ke nilai default.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function putMany(array $pairs, bool $allowNull = false): array
    {
        $before = self::values();
        $changes = [];

        foreach ($pairs as $key => $value) {
            if (! $allowNull && $value === null) {
                $value = '';
            }

            $old = $before[$key] ?? null;
            self::updateOrCreate(['key' => $key], ['value' => $value]);

            if ($old !== $value) {
                $changes[$key] = in_array($key, ActivityLog::MASKED, true)
                    ? ['old' => $old === null ? null : '••••••', 'new' => '••••••']
                    : ['old' => $old, 'new' => $value];
            }
        }

        return $changes;
    }
}
