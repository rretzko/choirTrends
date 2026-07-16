<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RepresentationConfidence;
use App\Enums\RepresentationSourceType;
use App\Enums\RepresentationStatus;
use Database\Factories\ArtistRepresentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $source_name
 * @property string|null $source_url
 * @property string|null $source_excerpt
 * @property-read Artist $artist
 * @property-read RepresentationCategory $representationCategory
 */
class ArtistRepresentation extends Model
{
    /** @use HasFactory<ArtistRepresentationFactory> */
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'representation_category_id',
        'source_type',
        'source_name',
        'source_url',
        'source_excerpt',
        'confidence',
        'status',
        'added_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => RepresentationSourceType::class,
            'confidence' => RepresentationConfidence::class,
            'status' => RepresentationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function representationCategory(): BelongsTo
    {
        return $this->belongsTo(RepresentationCategory::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
