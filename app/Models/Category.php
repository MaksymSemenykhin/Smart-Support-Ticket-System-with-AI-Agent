<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Category', description: 'Ticket category model')]
class Category extends Model
{
    #[OA\Property(property: 'id', description: 'Category ID', type: 'integer')]
    #[OA\Property(property: 'name', description: 'Category name', type: 'string')]
    #[OA\Property(property: 'slug', description: 'Category slug', type: 'string')]
    #[OA\Property(property: 'description', description: 'Category description', type: 'string', nullable: true)]
    #[OA\Property(property: 'is_active', description: 'Is category active', type: 'boolean')]
    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time')]
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
