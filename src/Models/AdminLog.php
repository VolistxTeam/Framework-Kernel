<?php

namespace Volistx\FrameworkKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Volistx\FrameworkKernel\Classes\UuidForKey;

class AdminLog extends Model
{
    use HasFactory;
    use UuidForKey;

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
        'access_token_id',
        'url',
        'method',
        'ip',
        'user_agent',
    ];
}
