<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SongTitleAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $song_title_id
 * @property string $grade_level_context
 * @property string|null $voicing
 * @property array{soprano: string, alto: string, tenor: string, bass: string}|null $difficulty_by_part
 * @property string|null $youtube_url
 * @property string|null $youtube_confidence
 * @property Carbon|null $youtube_verified_at
 * @property list<string>|null $citation_urls
 * @property string|null $model_version
 * @property Carbon $assessed_at
 */
class SongTitleAssessment extends Model
{
    /** @use HasFactory<SongTitleAssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'song_title_id',
        'grade_level_context',
        'voicing',
        'difficulty_by_part',
        'youtube_url',
        'youtube_confidence',
        'youtube_verified_at',
        'citation_urls',
        'model_version',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'difficulty_by_part' => 'array',
            'citation_urls' => 'array',
            'youtube_verified_at' => 'datetime',
            'assessed_at' => 'datetime',
        ];
    }

    public function songTitle(): BelongsTo
    {
        return $this->belongsTo(SongTitle::class);
    }
}
