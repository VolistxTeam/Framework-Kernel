<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;
use Volistx\FrameworkKernel\Enums\SubscriptionStatus;

class Subscription extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'activated_at',
        'expires_at',
        'cancels_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status'              => SubscriptionStatus::class,
        'activated_at'        => 'date:Y-m-d H:i:s',
        'expires_at'          => 'date:Y-m-d H:i:s',
        'cancels_at'          => 'date:Y-m-d H:i:s',
        'cancelled_at'        => 'date:Y-m-d H:i:s',
        'created_at'          => 'date:Y-m-d H:i:s',
        'updated_at'          => 'date:Y-m-d H:i:s',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function newUniqueId()
    {
        return Str::ulid()->toRfc4122();
    }

    protected function getUlidAttribute()
    {
        return Ulid::fromString($this->attributes['id']);
    }
}
