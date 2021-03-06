<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Volistx\FrameworkKernel\Helpers\UuidForKey;

class Subscription extends Model
{
    use HasFactory;
    use UuidForKey;

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
        'hmac_token',
        'plan_activated_at',
        'plan_expires_at',
    ];

    protected $casts = [
        'plan_activated_at' => 'date:Y-m-d H:i:s',
        'plan_expires_at'   => 'date:Y-m-d H:i:s',
        'created_at'        => 'date:Y-m-d H:i:s',
        'updated_at'        => 'date:Y-m-d H:i:s',
    ];

    public function personalTokens(): HasMany
    {
        return $this->hasMany(PersonalToken::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
