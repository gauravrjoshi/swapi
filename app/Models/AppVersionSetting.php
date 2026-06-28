<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores the single row of app version / force-update settings.
 *
 * There is always exactly one row (id = 1), seeded by the migration.
 * Use AppVersionSetting::first() to retrieve it.
 */
class AppVersionSetting extends Model
{
    protected $table = 'app_version_settings';

    protected $fillable = [
        'latest_version',
        'min_required_version',
        'force_update',
        'update_message',
        'android_store_url',
        'ios_store_url',
    ];

    protected $casts = [
        'force_update' => 'boolean',
    ];
}
