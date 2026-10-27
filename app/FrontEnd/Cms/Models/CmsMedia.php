<?php

namespace App\FrontEnd\Cms\Models;

use App\Admin\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsMedia extends Model
{
    use SoftDeletes;

    protected $fillable = ['disk', 'path', 'original_filename', 'mime_type', 'size_bytes', 'alt_text', 'is_public', 'uploaded_by'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'size_bytes' => 'integer'];
    }

    public function url(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
