<?php

namespace App\Models;

use App\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceVersion extends Model
{

    use BuildsQueries;

    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'service_id', 'amount', 'unit', 'price_type', 'status',
        'effective_from', 'effective_to', 'source_note',
        'created_by', 'approved_by', 'approved_at', 'is_active',
    ];

    protected $appends = ['status_badge', 'formatted_amount'];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'effective_from' => 'date:Y-m-d',
            'effective_to'   => 'date:Y-m-d',
            'approved_at'    => 'datetime',
            'is_active'      => 'boolean',
        ];
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'Draft'    => 'warning',
            'Approved' => 'success',
            'Retired'  => 'secondary',
        ];
        $color = $map[$this->status] ?? 'secondary';

        return "<span class='badge badge-light-{$color}'>{$this->status}</span>";
    }

    public function getFormattedAmountAttribute(): string
    {
        return '৳' . number_format($this->amount, 0, '.', ',');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
