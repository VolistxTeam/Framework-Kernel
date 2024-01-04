<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

class UserLog extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'subscription_id',
        'url',
        'method',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i:s',
        'url'        => 'encrypted',
        'method'     => 'encrypted',
        'ip'         => 'encrypted',
    ];

    public function newUniqueId()
    {
        return Str::ulid()->toRfc4122();
    }

    protected function getUlidAttribute()
    {
        return Ulid::fromString($this->attributes['id']);
    }
}
