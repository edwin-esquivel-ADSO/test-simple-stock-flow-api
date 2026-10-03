<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

final class SaleModel extends Model
{
    protected $table = 'sale';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sold_at',
        'sold_by_username',
        'sold_by_user_id',
    ];

    protected $casts = [
        'sold_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    public function items()
    {
        return $this->hasMany(SaleItemModel::class, 'sale_id', 'id');
    }
}
