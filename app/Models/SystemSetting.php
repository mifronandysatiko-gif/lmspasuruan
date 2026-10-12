<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
        'field_type',
        'label',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        if (!$setting) {
            return $default;
        }

        if ($setting->field_type === 'boolean') {
            return filter_var($setting->setting_value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($setting->field_type === 'number') {
            return is_numeric($setting->setting_value) ? (int) $setting->setting_value : $default;
        }

        return $setting->setting_value ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );
    }
}
