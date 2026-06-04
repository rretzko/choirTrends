<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int|null $ensemble_id
 * @property int $sort_order
 * @property int|null $ensemble_sort_order
 * @property string|null $ensemble_director
 * @property string|null $video_path
 * @property string|null $video_visibility
 * @property string|null $video_uploaded_at
 * @property string|null $notes
 */
class ProgramSongTitlePivot extends Pivot {}
