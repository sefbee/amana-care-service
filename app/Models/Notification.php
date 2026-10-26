<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $notification_id
 * @property string $message_body
 * @property string $message_title
 * @property string|null $extra
 * @property int|null $notification_type
 * @property string|null $sent_by
 * @property string|null $reference
 * @property string|null $user_id old
 * @property int|null $created_by_user_id FK: users - id
 * @property string|null $date old
 * @property string|null $time old
 * @property int $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification query()
 *
 * @mixin \Eloquent
 */
class Notification extends Model
{
    public const REFERENCE_COMMUNICATION = 'communication';

    protected $table = 'tbl_notification';

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'message_title', 'message_body', 'extra', 'notification_type', 'sent_by', 'reference', 'reference_id', 'created_by_user_id', 'status',
    ];

    protected $append = ['formatted_created_at'];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, h:i A') : null;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tbl_notification_assign', 'tbl_notification_notification_id', 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
