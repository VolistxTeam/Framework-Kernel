<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Enums\RateLimitMode;

class PersonalToken extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret',
        'secret_salt',
        'rate_limit_mode',
        'permissions',
        'ip_rule',
        'ip_range',
        'country_rule',
        'country_range',
        'hmac_token',
        'activated_at',
        'expires_at',
        'hidden',
        'disable_logging',
    ];

    protected $casts = [
        'permissions'     => 'array',
        'rate_limit_mode' => RateLimitMode::class,
        'ip_rule'         => AccessRule::class,
        'ip_range'        => 'array',
        'country_rule'    => AccessRule::class,
        'country_range'   => 'array',
        'activated_at'    => 'date:Y-m-d H:i:s',
        'expires_at'      => 'date:Y-m-d H:i:s',
        'hidden'          => 'boolean',
        'disable_logging' => 'boolean',
        'created_at'      => 'date:Y-m-d H:i:s',
        'updated_at'      => 'date:Y-m-d H:i:s',
    ];

    public function setCountryRangeAttribute($value)
    {
        $this->attributes['country_range'] = json_encode(array_map('strtoupper', $value));
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
