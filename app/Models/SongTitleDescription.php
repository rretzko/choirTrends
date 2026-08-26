<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorshipType;
use Database\Factories\SongTitleDescriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $song_title_id
 * @property string $description
 * @property AuthorshipType $authorship_type
 * @property int|null $authorship_id
 * @property int|null $repertoire_query_id
 * @property string|null $model_version
 */
class SongTitleDescription extends Model
{
    /** @use HasFactory<SongTitleDescriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'song_title_id',
        'description',
        'authorship_type',
        'authorship_id',
        'repertoire_query_id',
        'model_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
