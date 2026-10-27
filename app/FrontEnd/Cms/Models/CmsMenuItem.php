<?php

namespace App\FrontEnd\Cms\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsMenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['location', 'parent_id', 'cms_page_id', 'label', 'url', 'is_external', 'opens_new_tab', 'is_visible', 'sort_order'];

    protected function casts(): array
    {
        return ['is_external' => 'boolean', 'opens_new_tab' => 'boolean', 'is_visible' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }
}
