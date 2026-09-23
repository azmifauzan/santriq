<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (id 1) for app-wide settings that aren't scoped to a tenant.
 *
 * @property int $id
 * @property string|null $whatsapp_number
 */
class AppSetting extends Model
{
    public const DEFAULT_WHATSAPP_NUMBER = '+6285220150587';

    protected $fillable = ['whatsapp_number'];

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], ['whatsapp_number' => self::DEFAULT_WHATSAPP_NUMBER]);
    }
}
