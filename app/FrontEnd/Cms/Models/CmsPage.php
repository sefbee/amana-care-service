<?php

namespace App\FrontEnd\Cms\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'meta_title', 'meta_description', 'canonical_url', 'open_graph_media_id', 'status',
        'published_slug', 'published_metadata', 'published_at', 'published_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['published_metadata' => 'array', 'published_at' => 'datetime'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class)->orderBy('sort_order');
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->status !== 'Published' || ! is_array($this->published_metadata)
            || $this->draftSnapshot() !== $this->published_metadata;
    }

    /** @return array<string, mixed> */
    public function draftSnapshot(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'canonical_url' => $this->canonical_url,
            'open_graph_media_id' => $this->open_graph_media_id,
            'sections' => $this->sections->map(fn (CmsSection $section): array => [
                'id' => $section->id,
                'type' => $section->type,
                'content' => $section->content,
                'is_active' => $section->is_active,
                'sort_order' => $section->sort_order,
            ])->values()->all(),
        ];
    }
}
