<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use Illuminate\Database\Eloquent\Model;

final class ProductModel extends Model
{
    protected $table = 'product';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'price',
        'stock',
        'category_id',
        'image_key',
        'deleted_at',
        'version',
    ];

    protected $casts = [
        'price' => 'string',
        'stock' => 'integer',
        'version' => 'integer',
        'deleted_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }
}
