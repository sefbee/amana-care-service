<?php

namespace App\FrontEnd\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSetting extends Model
{
    protected $fillable = ['key', 'value', 'published_value', 'is_public', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'array', 'published_value' => 'array', 'is_public' => 'boolean'];
    }
}
