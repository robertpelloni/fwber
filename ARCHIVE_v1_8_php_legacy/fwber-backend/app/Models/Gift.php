<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $cost
 * @property string $icon_url
 * @property string $category
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereIconUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'icon_url',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'integer',
    ];
}
