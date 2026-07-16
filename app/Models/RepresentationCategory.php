<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RepresentationGroup;
use Database\Factories\RepresentationCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $label
 * @property string $slug
 * @property int $sort_order
 */
class RepresentationCategory extends Model
{
    /** @use HasFactory<RepresentationCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'group',
        'label',
        'slug',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'group' => RepresentationGroup::class,
            'sort_order' => 'integer',
        ];
    }

    public function artistRepresentations(): HasMany
    {
        return $this->hasMany(ArtistRepresentation::class);
    }
}
