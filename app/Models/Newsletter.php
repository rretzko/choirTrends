<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsletterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    /** @use HasFactory<NewsletterFactory> */
    use HasFactory;

    protected $fillable = [
        'subject',
        'headline',
        'intro',
        'sections',
        'cta_text',
        'cta_url',
        'closing',
        'sent_at',
        'recipient_count',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }
}
