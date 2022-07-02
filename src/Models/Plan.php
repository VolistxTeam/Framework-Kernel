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
        'description',
        'data',
        'price'
    ];

    protected $casts = [
        'data' => 'array',
        'price' => 'float'
    ];

    public function subscriptions(): HasMany
    {
        return $this->HasMany(Subscription::class);
    }
}
