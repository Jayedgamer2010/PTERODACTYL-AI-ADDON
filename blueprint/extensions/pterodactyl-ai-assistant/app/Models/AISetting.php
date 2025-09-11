<?php

namespace Blueprint\Extensions\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class AISetting extends Model
{
    protected $table = 'ai_settings';

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    public static function getSetting(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setSetting(string $key, $value, ?string $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }
}
