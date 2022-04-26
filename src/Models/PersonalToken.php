<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Helpers\UuidForKey;

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
        'ip_rule',
        'ip_range',
        'country_rule',
        'country_range',
        'activated_at',
        'expires_at',
        'hidden',
    ];

    protected $casts = [
        'permissions'   => 'array',
        'ip_rule'       => AccessRule::class,
        'ip_range'      => 'array',
        'country_rule'  => AccessRule::class,
        'country_range' => 'array',
        'activated_at'  => 'date:Y-m-d H:i:s',
        'expires_at'    => 'date:Y-m-d H:i:s',
        'hidden'        => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    //mutator to set country range to upper
    public function setCountryRangeAttribute($value)
    {
        $this->attributes['country_range'] = json_encode(array_map('strtoupper', $value));
    }
}
