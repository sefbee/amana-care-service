<?php

namespace App\Concerns;

trait BuildsQueries
{
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function isActive(int $value = 0)
    {
        return $this->is_active == $value ? 'selected' : '';
    }

    public function getStatus()
    {
        if ($this->is_active) {
            return "<span class='badge badge-success'>Active</span>";
        }

        return "<span class='badge badge-danger'>Inactive</span>";
    }

    public function toggleButton($url, $label = '')
    {
        $html = '<label class="form-check form-switch form-switch-sm form-check-custom form-check-solid" >';

        $html .= '<input class="form-check-input form-switch-input" name="toggle_input" type="checkbox" ' . ($this->is_active == 1 ? 'checked' : '') . ' data-target="' . $url . '">';

        if ($label) {
            $html .= '<span class="form-check-label text-muted">' . $label . '</span>';
        }
        $html .= '</label>';

        return $html;
    }

    public function editButton($url)
    {
        return '<a href="' . $url . '" class="btn btn-light-primary btn-icon btn-sm me-2" title="Edit"><i class="fas fa-edit"></i></a>';
    }

    public function deleteButton($url)
    {
        $formId = 'destroy-form-' . $this->id;

        return "<a class='btn btn-light-danger btn-icon btn-sm me-2' title='Delete' href='#'
                onclick='event.preventDefault(); confirmDelete(`{$formId}`);'><i class='fas fa-trash-alt'></i>
            </a>

            <form id='{$formId}' action='" . $url . "' method='POST' class='d-none'>
                <input type='hidden' name='_token' value='" . csrf_token() . "'>
                <input type='hidden' name='_method' value='DELETE'>
            </form>";
    }

    public function noImageUrl($url = null)
    {
        return asset($url ?? 'img/no-photo.jpg');
    }
}
