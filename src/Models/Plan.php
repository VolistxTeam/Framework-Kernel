<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Volistx\FrameworkKernel\Helpers\UuidForKey;

class Plan extends Model
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
    protected $table = 'plans';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'tag',
        'description',
        'data',
        'price',
        'custom',
        'tier',
    ];

    protected $casts = [
        'data'       => 'array',
        'price'      => 'float',
        'custom'     => 'boolean',
        'tier'       => 'integer',
        'created_at' => 'date:Y-m-d H:i:s',
        'updated_at' => 'date:Y-m-d H:i:s',
    ];

    public function subscriptions(): HasMany
    {
        return $this->HasMany(Subscription::class);
    }
}
