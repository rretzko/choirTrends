<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\VoicePart;
use Database\Factories\SongTitleDifficultyObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $song_title_id
 * @property VoicePart $voice_part
 * @property DifficultyLevel $difficulty_label
 * @property int $difficulty_value
 * @property AuthorshipType $authorship_type
 * @property int|null $authorship_id
 * @property int|null $repertoire_query_id
 * @property string|null $citation_url
 * @property string|null $model_version
 */
class SongTitleDifficultyObservation extends Model
{
    /** @use HasFactory<SongTitleDifficultyObservationFactory> */
    use HasFactory;

    protected $fillable = [
        'song_title_id',
        'voice_part',
        'difficulty_label',
        'difficulty_value',
        'authorship_type',
        'authorship_id',
        'repertoire_query_id',
        'citation_url',
        'model_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voice_part' => VoicePart::class,
            'difficulty_label' => DifficultyLevel::class,
            'authorship_type' => AuthorshipType::class,
        ];
    }

    public function songTitle(): BelongsTo
    {
        return $this->belongsTo(SongTitle::class);
    }

    public function repertoireQuery(): BelongsTo
    {
        return $this->belongsTo(RepertoireQuery::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorship_id');
    }
}
