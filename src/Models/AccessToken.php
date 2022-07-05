<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Helpers\UuidForKey;

class AccessToken extends Model
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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'secret',
        'secret_salt',
        'permissions',
        'ip_rule',
        'ip_range',
        'country_rule',
        'country_range',
    ];

    protected $casts = [
        'permissions'   => 'array',
        'ip_rule'       => AccessRule::class,
        'ip_range'      => 'array',
        'country_rule'  => AccessRule::class,
        'country_range' => 'array',
        'created_at'    => 'date:Y-m-d H:i:s',
        'updated_at'    => 'date:Y-m-d H:i:s',
    ];

    public function setCountryRangeAttribute($value)
    {
        $this->attributes['country_range'] = json_encode(array_map('strtoupper', $value));
    }
}
