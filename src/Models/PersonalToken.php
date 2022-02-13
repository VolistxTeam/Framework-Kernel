<?php

namespace VolistxTeam\VSkeletonKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VolistxTeam\VSkeletonKernel\Classes\UuidForKey;

class PersonalToken extends Model
{
    use HasFactory;
    use UuidForKey;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'subscription_id',
        'key',
        'secret',
        'secret_salt',
        'permissions',
        'whitelist_range',
        'activated_at',
        'expires_at',
    ];

    protected $casts = [
        'permissions'     => 'array',
        'whitelist_range' => 'array',
        'activated_at'    => 'date:Y-m-d H:i:s',
        'expires_at'      => 'date:Y-m-d H:i:s',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
