<?php

namespace App\FrontEnd\Cms\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cms_page_id', 'type', 'content', 'published_content', 'is_active', 'published_is_active',
        'sort_order', 'published_sort_order', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['content' => 'array', 'published_content' => 'array', 'is_active' => 'boolean', 'published_is_active' => 'boolean'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }
}
