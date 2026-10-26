<?php

namespace App\Traits;

trait CommonAttributes
{
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function getStatusAttribute()
    {
        $class = $this->is_active ? 'success' : 'danger';
        $text = $this->is_active ? 'Active' : 'Inactive';

        return "<span class='badge badge-light-$class'>$text</span></span>";
    }

    public function getPublishedAttribute()
    {
        $class = $this->is_published ? 'primary' : 'warning';
        $text = $this->is_published ? 'Published' : 'Draft';

        return "<span class='badge badge-light-$class'>$text</span></span>";
    }
}
