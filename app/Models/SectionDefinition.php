<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionDefinition extends Model
{
    protected $fillable = [
        'key',
        'label',
        'component',
        'default_config',
        'is_system',
        'is_enabled_by_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_config' => 'array',
            'is_system' => 'boolean',
            'is_enabled_by_default' => 'boolean',
        ];
    }
}
