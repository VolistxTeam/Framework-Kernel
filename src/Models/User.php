<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;
use Volistx\FrameworkKernel\Facades\PersonalTokens;

class User extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'date:Y-m-d H:i:s',
        'updated_at' => 'date:Y-m-d H:i:s',
    ];

    public function subscriptions(): HasMany
    {
        return $this->HasMany(Subscription::class);
    }

    public function personal_tokens(): HasMany
    {
        return $this->HasMany(PersonalTokens::class);
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
