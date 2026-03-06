<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $batch_id
 * @property int $user_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatchUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatchUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatchUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatchUser whereBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatchUser whereUserId($value)
 *
 * @mixin \Eloquent
 */
class BatchUser extends Model
{
    protected $guarded = [];
}
