<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public const SECRET_KEYS = ['openai_api_key', 'hostinger_mail_api_token'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();
        if (! $setting || $setting->value === null) {
            return $default;
        }

        if (in_array($key, self::SECRET_KEYS, true)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $setting->value;
    }

    public static function put(string $key, mixed $value): void
    {
        $stored = $value === null ? null : (string) $value;
        if ($value !== null && in_array($key, self::SECRET_KEYS, true)) {
            $stored = Crypt::encryptString((string) $value);
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $stored]);
    }

    public static function allValues(bool $includeSecrets = false): array
    {
        return static::query()
            ->get()
            ->mapWithKeys(function (self $setting) use ($includeSecrets) {
                if (! $includeSecrets && in_array($setting->key, self::SECRET_KEYS, true)) {
                    return [$setting->key => $setting->value ? '••••••••' : ''];
                }

                return [$setting->key => static::get($setting->key)];
            })
            ->all();
    }
}
