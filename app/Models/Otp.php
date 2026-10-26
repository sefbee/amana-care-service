<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $phone
 * @property string $otp
 * @property string|null $secret
 * @property string|null $expire_ts
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read mixed $published
 * @property-read mixed $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereExpireTs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Otp whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Otp extends Model
{
    use CommonAttributes;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'otp',
        'secret',
        'expire_ts',
    ];
}
