<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoryModel extends Model
{
    use HasFactory;

    protected $table = 't_sub_category';

    protected $fillable = [
        'name',
        'category_id',
        'image',
    ];

    // Define the relationship: A category has many products, joined by the 'name' column
    public function products()
    {
        return $this->hasMany(ProductModel::class, 'sub_category', 'name');
    }
}
